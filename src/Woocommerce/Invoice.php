<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 * User: manveru
 * Date: 08.06.2026
 *
 */

namespace Netivo\Module\WooCommerce\B2B\Woocommerce;

use Netivo\Module\WooCommerce\B2B\Module;
use Netivo\Module\WooCommerce\B2B\Woocommerce\Gateway\Proforma;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

/**
 * Integrates the PDF Invoices & Packing Slips plugin with the Proforma gateway.
 *
 * - Initialises and saves the invoice when a Proforma order goes on-hold.
 * - Attaches the invoice PDF to the customer on-hold email and admin new-order email.
 * - Redirects the invoice template to woocommerce/pdf/proforma.php for Proforma orders.
 * - Renames the document file to proforma-{n}.pdf for Proforma orders.
 * - Renames the "Invoice" document title to "Proforma" for Proforma orders.
 *
 * Requires the "PDF Invoices & Packing Slips" plugin (wcpdf_get_document()).
 */
class Invoice {

	/** Email IDs that should carry the proforma invoice attachment. */
	private const ATTACH_TO_EMAILS = [
		'customer_on_hold_order',
		'new_order',
	];

	public function __construct() {
		add_action( 'woocommerce_order_status_on-hold', [ $this, 'generate_proforma_document' ], 10, 2 );
		add_filter( 'woocommerce_email_attachments', [ $this, 'attach_to_email' ], 100, 4 );
		add_filter( 'wpo_wcpdf_template_file', [ $this, 'use_proforma_template' ], 10, 3 );
		add_filter( 'wpo_wcpdf_filename', [ $this, 'rename_proforma_filename' ], 10, 5 );
		add_filter( 'wpo_wcpdf_document_title', [ $this, 'rename_title_for_proforma' ], 10, 2 );
		add_filter( 'wpo_wcpdf_template_styles', [ $this, 'fix_proforma_font' ], 10, 2 );
	}

	/**
	 * Initialises and saves the invoice document for Proforma orders.
	 *
	 * @param int       $order_id
	 * @param \WC_Order $order
	 */
	public function generate_proforma_document( int $order_id, \WC_Order $order ): void {
		if ( ! function_exists( 'wcpdf_get_document' ) ) {
			return;
		}

		if ( $order->get_payment_method() !== Proforma::ID ) {
			return;
		}

		$document = wcpdf_get_document( 'invoice', $order, true );
		if ( $document ) {
			$document->save();
		}
	}

	/**
	 * Attaches the proforma invoice PDF to WooCommerce order emails.
	 * Runs at priority 100, after the plugin's own attachment handler (99).
	 *
	 * @param array          $attachments
	 * @param string         $email_id
	 * @param \WC_Order|mixed $order
	 * @param \WC_Email|null $email
	 * @return array
	 */
	public function attach_to_email( array $attachments, string $email_id, $order, $email = null ): array {
		if ( ! function_exists( 'wcpdf_get_document' ) || ! function_exists( 'wcpdf_get_document_file' ) ) {
			return $attachments;
		}

		if ( ! in_array( $email_id, self::ATTACH_TO_EMAILS, true ) ) {
			return $attachments;
		}

		if ( ! $order instanceof \WC_Order ) {
			return $attachments;
		}

		if ( $order->get_payment_method() !== Proforma::ID ) {
			return $attachments;
		}

		try {
			$document = wcpdf_get_document( 'invoice', $order, true );
			if ( ! $document ) {
				return $attachments;
			}

			$file = wcpdf_get_document_file( $document, 'pdf' );
			if ( $file ) {
				$attachments[] = $file;
			}
		} catch ( \Throwable $e ) {
			wcpdf_log_error( 'B2B proforma attachment error: ' . $e->getMessage(), 'critical' );
		}

		return $attachments;
	}

	/**
	 * Redirects the invoice template to woocommerce/pdf/proforma.php for Proforma orders.
	 * Only replaces the main invoice.php file, not style.css or other assets.
	 *
	 * @param string          $file_path     Resolved template file path.
	 * @param string          $document_type Document type slug.
	 * @param \WC_Order|null  $order
	 * @return string
	 */
	public function use_proforma_template( string $file_path, string $document_type, $order ): string {
		if ( $document_type !== 'invoice' ) {
			return $file_path;
		}

		if ( ! str_ends_with( $file_path, 'invoice.php' ) ) {
			return $file_path;
		}

		if ( ! $order instanceof \WC_Order || $order->get_payment_method() !== Proforma::ID ) {
			return $file_path;
		}

		$custom = get_template_directory() . '/woocommerce/pdf/proforma.php';
		if ( file_exists( $custom ) ) {
			return $custom;
		}

		return $file_path;
	}

	/**
	 * Renames the PDF file from invoice-{n}.pdf to proforma-{n}.pdf for Proforma orders.
	 *
	 * @param string   $filename      Current filename (e.g. invoice-5.pdf).
	 * @param string   $document_type Document type slug.
	 * @param array    $order_ids     Array of order IDs.
	 * @param string   $context       Context: 'download', 'email', etc.
	 * @param array    $args          Additional arguments.
	 * @return string
	 */
	public function rename_proforma_filename( string $filename, string $document_type, array $order_ids, string $context, array $args ): string {
		if ( $document_type !== 'invoice' ) {
			return $filename;
		}

		$order_id = ! empty( $order_ids ) ? reset( $order_ids ) : null;
		if ( ! $order_id ) {
			return $filename;
		}

		$order = wc_get_order( (int) $order_id );
		if ( ! $order || $order->get_payment_method() !== Proforma::ID ) {
			return $filename;
		}

		// Replace the "invoice" base name with "proforma", keep the suffix (-{n}.pdf).
		$suffix = substr( $filename, (int) strpos( $filename, '-' ) );
		return 'proforma' . $suffix;
	}

	/**
	 * Overrides the template stylesheet font for Proforma documents so that
	 * DomPDF uses 'dejavu sans' — the only bundled font that supports Polish
	 * characters (ąćęłńóśźż, zł). The generic 'sans-serif' maps to Helvetica
	 * in DomPDF and does NOT render Polish glyphs.
	 *
	 * @param string $styles       CSS from the template's style.css.
	 * @param object $document     OrderDocument instance.
	 * @return string
	 */
	public function fix_proforma_font( string $styles, $document ): string {
		if ( $document->get_type() !== 'invoice' ) {
			return $styles;
		}

		$order = $document->order ?? null;
		if ( ! $order instanceof \WC_Order || $order->get_payment_method() !== Proforma::ID ) {
			return $styles;
		}

		return $styles . "\nbody, table, th, td, p, h1, h2, h3, h4, div, span { font-family: 'dejavu sans', sans-serif; }\n";
	}

	/**
	 * Changes the invoice document title to "Proforma" when the associated
	 * order was paid via the Proforma gateway.
	 *
	 * @param string $title    Current document title.
	 * @param object $document WPO\IPS\Documents\OrderDocument instance.
	 * @return string
	 */
	public function rename_title_for_proforma( string $title, $document ): string {
		if ( $document->get_type() !== 'invoice' ) {
			return $title;
		}

		$order = $document->order ?? null;
		if ( $order instanceof \WC_Order && $order->get_payment_method() === Proforma::ID ) {
			return __( 'Proforma', 'netivo' );
		}

		return $title;
	}
}
