<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 *
 */

namespace Netivo\Module\WooCommerce\B2B\Woocommerce\Email;

use Netivo\Module\WooCommerce\B2B\Admin\Controller\Requests;
use Netivo\Module\WooCommerce\B2B\Module;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

/**
 * Notifies the site administrator whenever a new B2B account request comes in through the form.
 */
class RequestReceivedAdmin extends \WC_Email {

	protected array $form_data = array();

	protected bool $new_user = false;

	protected string $requests_url = '';

	public function __construct() {

		$this->id             = 'b2b_request_received_admin';
		$this->customer_email = false;
		$this->title          = 'Nowe zgłoszenie B2B (Administrator)';
		$this->description    = 'Powiadomienie wysyłane do Administratora po wypełnieniu formularza B2B przez użytkownika';
		$this->template_html  = 'emails/b2b-request-received-admin.php';
		$this->template_plain = 'emails/plain/b2b-request-received-admin.php';
		$this->placeholders   = array(
			'{site_title}' => $this->get_blogname(),
		);

		add_action( 'nt_b2b_request_sent', array( $this, 'trigger' ), 10, 3 );

		parent::__construct();

		$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );

	}

	function trigger( $user_email, $form_data, $new_user = false ): void {
		$this->setup_locale();

		$this->form_data    = $form_data;
		$this->new_user     = $new_user;
		$this->requests_url = admin_url( Requests::$list_url );

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	/**
	 * Get email subject.
	 *
	 * @return string
	 * @since  3.1.0
	 */
	public function get_default_subject(): string {
		return __( 'Nowe zgłoszenie o konto B2B', 'netivo' );
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 * @since  3.1.0
	 */
	public function get_default_heading(): string {
		return __( 'Otrzymano nowe zgłoszenie B2B', 'netivo' );
	}

	/**
	 * Get content html.
	 *
	 * @return string
	 */
	public function get_content_html(): string {
		return wc_get_template_html(
			$this->template_html,
			array(
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => true,
				'plain_text'    => false,
				'form_data'     => $this->form_data,
				'new_user'      => $this->new_user,
				'requests_url'  => $this->requests_url,
				'email'         => $this,
			),
			'',
			Module::get_module_path() . '/views/wc/'
		);
	}

	/**
	 * Get content plain.
	 *
	 * @return string
	 */
	public function get_content_plain(): string {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'email_heading' => $this->get_heading(),
				'form_data'     => $this->form_data,
				'new_user'      => $this->new_user,
				'requests_url'  => $this->requests_url,
				'sent_to_admin' => true,
				'plain_text'    => true,
				'email'         => $this,
			),
			'',
			Module::get_module_path() . '/views/wc/'
		);
	}

	/**
	 * Initialize Settings Form Fields
	 *
	 * @since 2.0
	 */
	public function init_form_fields(): void {

		$this->form_fields = array(
			'enabled'    => array(
				'title'   => 'Enable/Disable',
				'type'    => 'checkbox',
				'label'   => 'Enable this email notification',
				'default' => 'yes'
			),
			'recipient'  => array(
				'title'       => 'Recipient(s)',
				'type'        => 'text',
				'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to <code>%s</code>.', 'woocommerce' ), esc_attr( get_option( 'admin_email' ) ) ),
				'placeholder' => '',
				'default'     => ''
			),
			'subject'    => array(
				'title'       => 'Subject',
				'type'        => 'text',
				'description' => sprintf( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', $this->subject ),
				'placeholder' => '',
				'default'     => ''
			),
			'heading'    => array(
				'title'       => 'Email Heading',
				'type'        => 'text',
				'description' => sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.' ), $this->heading ),
				'placeholder' => '',
				'default'     => ''
			),
			'email_type' => array(
				'title'       => 'Email type',
				'type'        => 'select',
				'description' => 'Choose which format of email to send.',
				'default'     => 'html',
				'class'       => 'email_type',
				'options'     => array(
					'plain'     => __( 'Plain text', 'woocommerce' ),
					'html'      => __( 'HTML', 'woocommerce' ),
					'multipart' => __( 'Multipart', 'woocommerce' ),
				)
			)
		);
	}
}
