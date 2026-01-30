<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 * User: manveru
 * Date: 30.01.2026
 * Time: 12:56
 *
 */

namespace Netivo\Module\WooCommerce\B2B\Woocommerce\Email;

use Netivo\Module\WooCommerce\B2B\Module;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class RequestDenied extends \WC_Email {

	protected ?WP_User $user = null;

	protected string $reason;

	public function __construct() {

		$this->id             = 'request_denied';
		$this->customer_email = true;
		$this->title          = 'Odrzucenie zgłoszenia B2B';
		$this->description    = 'Powiadomienie "Odrzucenia zgłoszenia" jest wysyłane do użytkownika po odrzuceniu zgłoszenia B2B przez Administratora';
		$this->template_html  = 'emails/b2b-request-denied.php';
		$this->template_plain = 'emails/plain/b2b-request-denied.php';
		$this->placeholders   = array(
			'{site_title}' => $this->get_blogname(),
		);


		add_action( 'nt_b2b_request_denied', array( $this, 'trigger' ), 10, 2 );

		parent::__construct();

	}

	function trigger( $user, $reason ): void {
		$this->setup_locale();

		if ( ! empty( $user ) ) {
			if ( ! is_a( $user, WP_User::class ) ) {
				$user = get_user_by( 'id', $user );
			}
		}

		$this->recipient = $user->user_email;
		$this->user      = $user;
		$this->reason    = $reason;

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
		return __( 'Twoje zgłoszenie B2B zostało zaakceptowane', 'netivo' );
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 * @since  3.1.0
	 */
	public function get_default_heading(): string {
		return __( 'Zgłoszenie zaakceptowane', 'netivo' );
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
				'sent_to_admin' => false,
				'plain_text'    => false,
				'user'          => $this->user,
				'reason'        => $this->reason,
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
				'user'          => $this->user,
				'reason'        => $this->reason,
				'sent_to_admin' => false,
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