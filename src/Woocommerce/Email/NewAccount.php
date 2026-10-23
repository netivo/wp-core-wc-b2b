<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 */

namespace Netivo\Module\WooCommerce\B2B\Woocommerce\Email;

use Netivo\Module\WooCommerce\B2B\Module;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class NewAccount extends \WC_Email {

	protected ?WP_User $user = null;

	protected string $set_password_url = '';

	public function __construct() {

		$this->id             = 'b2b_new_account';
		$this->customer_email = true;
		$this->title          = 'Nowe konto B2B';
		$this->description    = 'Wysyłane do użytkownika po rejestracji konta B2B. Zawiera link do ustawienia hasła na stronie frontowej.';
		$this->template_html  = 'emails/b2b-new-account.php';
		$this->template_plain = 'emails/plain/b2b-new-account.php';
		$this->placeholders   = array(
			'{site_title}' => $this->get_blogname(),
		);

		add_action( 'nt_b2b_new_account', array( $this, 'trigger' ), 10, 1 );

		parent::__construct();
	}

	public function trigger( int $user_id ): void {
		$this->setup_locale();

		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			$this->restore_locale();
			return;
		}

		$reset_key = get_password_reset_key( $user );
		if ( is_wp_error( $reset_key ) ) {
			$this->restore_locale();
			return;
		}

		$this->user             = $user;
		$this->recipient        = $user->user_email;
		$this->set_password_url = add_query_arg(
			array(
				'key'   => $reset_key,
				'id'    => $user->ID,
				'login' => rawurlencode( $user->user_login ),
			),
			wc_get_account_endpoint_url( 'lost-password' )
		);

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	public function get_default_subject(): string {
		return __( 'Twoje konto B2B zostało utworzone', 'netivo' );
	}

	public function get_default_heading(): string {
		return __( 'Witaj w warfactory.pl!', 'netivo' );
	}

	public function get_content_html(): string {
		return wc_get_template_html(
			$this->template_html,
			array(
				'email_heading'    => $this->get_heading(),
				'sent_to_admin'    => false,
				'plain_text'       => false,
				'user'             => $this->user,
				'set_password_url' => $this->set_password_url,
				'email'            => $this,
			),
			'',
			Module::get_module_path() . '/views/wc/'
		);
	}

	public function get_content_plain(): string {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'email_heading'    => $this->get_heading(),
				'user'             => $this->user,
				'set_password_url' => $this->set_password_url,
				'sent_to_admin'    => false,
				'plain_text'       => true,
				'email'            => $this,
			),
			'',
			Module::get_module_path() . '/views/wc/'
		);
	}

	public function init_form_fields(): void {
		$this->form_fields = array(
			'enabled'    => array(
				'title'   => 'Enable/Disable',
				'type'    => 'checkbox',
				'label'   => 'Enable this email notification',
				'default' => 'yes',
			),
			'subject'    => array(
				'title'       => 'Subject',
				'type'        => 'text',
				'description' => sprintf( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', $this->subject ),
				'placeholder' => '',
				'default'     => '',
			),
			'heading'    => array(
				'title'       => 'Email Heading',
				'type'        => 'text',
				'description' => sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.' ), $this->heading ),
				'placeholder' => '',
				'default'     => '',
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
				),
			),
		);
	}
}
