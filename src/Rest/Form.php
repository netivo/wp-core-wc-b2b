<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 * User: manveru
 * Date: 20.01.2026
 * Time: 11:42
 *
 */

namespace Netivo\Module\WooCommerce\B2B\Rest;

use Netivo\Core\RestController;
use Netivo\Module\WooCommerce\B2B\Module;
use Override;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

/**
 * Handles the registration of routes and user registration for a B2B account, with CAPTCHA validation.
 */
class Form extends RestController {
	/**
	 * Namespace of Rest Endpoint
	 *
	 * @var string
	 */
	protected string $namespace = 'netivo';

	/**
	 * Base of Rest endpoint
	 *
	 * @var string
	 */
	protected string $base = 'b2b-form';

	/**
	 * Version of the Rest endpoint
	 *
	 * @var string
	 */
	protected string $version = 'v1';

	/**
	 * Registers the necessary routes for handling form submission.
	 *
	 * @return void
	 */
	#[Override]
	public function register_routes(): void {
		$this->build_route( [ $this, 'form_register' ], 'POST' );
	}

	/**
	 * Handles the user registration process for a B2B account.
	 *
	 * This method validates the provided registration data, checks if the user already exists,
	 * processes the registration, and returns an appropriate response. It also validates the captcha.
	 *
	 * @param \WP_REST_Request $request The incoming REST API request containing user registration data:
	 *     - personal_name (string): The first name of the user.
	 *     - personal_surname (string): The last name of the user.
	 *     - company_name (string): The name of the user's company.
	 *     - company_nip (string): The tax identification number of the company.
	 *     - email_address (string): The email address of the user.
	 *     - phone_number (string): The phone number of the user.
	 *     - message (string): Additional message provided by the user.
	 *     - captcha (string): Captcha value for validation.
	 *
	 * @return array|\WP_Error Returns an array on successful registration containing the status and message:
	 *     - status (string): The status of the operation ('success').
	 *     - message (string): A confirmation message regarding the account registration.
	 *
	 *     Returns a \WP_Error object in case of an error with an appropriate error message:
	 *     - 500 code if captcha validation fails or if required fields are missing.
	 */
	public function form_register( \WP_REST_Request $request ): array|\WP_Error {
		$personal_name    = sanitize_text_field( $request->get_param( 'first_name' ) );
		$personal_surname = sanitize_text_field( $request->get_param( 'last_name' ) );
		$company_name     = sanitize_text_field( $request->get_param( 'company_name' ) );
		$company_nip      = sanitize_text_field( $request->get_param( 'nip' ) );
		$email_address    = sanitize_text_field( $request->get_param( 'email' ) );
		$phone_number     = sanitize_text_field( $request->get_param( 'phone' ) );
		$message          = sanitize_text_field( $request->get_param( 'message' ) );
		$captcha          = sanitize_text_field( $request->get_param( 'recaptcha' ) );
		if ( ! empty( $personal_name ) && ! empty( $personal_surname ) && ! empty( $company_name ) && ! empty( $company_nip ) &&
		     ! empty( $email_address ) && ! empty( $phone_number ) && ! empty( $message ) ) {
			if ( ! $this->validate_captcha( $captcha ) ) {
				return new \WP_Error( 500, __( 'Nieprawidłowa wartośc recaptcha.', 'netivo' ) );
			}

			$user_data = [
				'personal_name'    => $personal_name,
				'personal_surname' => $personal_surname,
				'company_name'     => $company_name,
				'email_address'    => $email_address,
				'phone_number'     => $phone_number,
				'company_nip'      => $company_nip
			];

			if ( Module::user_controller()->user_exists( $email_address ) ) {
				Module::user_controller()->update_user( $user_data, $message );
				$user_exists = true;
				$add_message = __( 'Do tego czasu możesz korzystać ze swojego konta jak dotychczas.', 'netivo' );
			} else {
				Module::user_controller()->register_user( $user_data, $message );
				$user_exists = false;
				$add_message = __( 'Na wskazany adres email dostałeś wiadomość ustawienia hasła. Do czasu akceptacji konta możesz używać jak zwyczajny użytkownik.', 'netivo' );
			}

			$user_data['message'] = $message;
			do_action( 'nt_b2b_request_sent', $email_address, $user_data, $user_exists );

			return [
				'status'  => 'success',
				'message' => __( 'Twoja prośba o założenie konta B2B została wysłana. Po akceptacji przez Administratora zostaniesz powiadomiony w specjalnej wiadomości.', 'netivo' ) . ' ' . $add_message
			];
		}

		return new \WP_Error( 500, __( 'Musisz wypełnić wszystkie wymagane pola.', 'netivo' ) );
	}

	/**
	 * Validates the CAPTCHA response by verifying it with the reCAPTCHA API.
	 *
	 * @param string $captcha The CAPTCHA response sent by the user.
	 *
	 * @return bool Returns true if the CAPTCHA is valid or the CAPTCHA verification is disabled, otherwise false.
	 */
	protected function validate_captcha( string $captcha ): bool {
		$captcha_key = get_option( 'nt_captcha_secret_key' );
		if ( empty( $captcha_key ) ) {
			return true;
		}
		$response = htmlspecialchars( $captcha );
		if ( ! empty( $response ) ) {
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify" );
			curl_setopt( $ch, CURLOPT_POST, 1 );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( array(
				'secret'   => get_option( 'nt_captcha_secret_key' ),
				'response' => $response
			) ) );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			$verify = curl_exec( $ch );
			curl_close( $ch );
			$captcha_success = json_decode( $verify );
			if ( $captcha_success->success === false || $captcha_success->action !== 'contact' ) {
				if ( $captcha_success->success === false || $captcha_success->action !== 'contact' ) {
					return false;
				}

				return true;
			}
		}

		return false;
	}
}