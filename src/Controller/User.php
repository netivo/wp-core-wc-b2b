<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 * User: manveru
 * Date: 20.01.2026
 * Time: 13:30
 *
 */

namespace Netivo\Module\WooCommerce\B2B\Controller;

use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class User {
	/**
	 * Checks whether a user with the given email address exists.
	 *
	 * @param string $email The email address to check for existence.
	 *
	 * @return bool Returns true if the user does not exist, false otherwise.
	 */
	public function user_exists( string $email ): bool {
		$user = get_user_by( 'email', $email );

		if ( $user ) {
			return true;
		}

		return false;
	}

	/**
	 * Registers a new user with the provided data and assigns B2B-related metadata.
	 *
	 * @param array $data An associative array of user data containing:
	 *                        - 'email_address' (string)   The email address of the user.
	 *                        - 'personal_name' (string)   The user's first name.
	 *                        - 'personal_surname' (string) The user's last name.
	 *                        - 'phone_number' (string)    The user's phone number.
	 *                        - 'company_name' (string)    The name of the user's company.
	 *                        - 'company_nip' (string)     The tax identification number of the company.
	 * @param string $message A message to include in the user's metadata for B2B purposes.
	 *
	 * @return \WP_Error|int|null Returns the user ID if registration is successful,
	 *                            a WP_Error object if an error occurs, or null in case of failure.
	 */
	public function register_user( array $data, string $message ): \WP_Error|int|null {
		$user_data = [
			'user_login'   => esc_attr( $data['email_address'] ),
			'user_email'   => esc_attr( $data['email_address'] ),
			'user_pass'    => esc_attr( wp_generate_password( 12, false ) ),
			'first_name'   => esc_attr( $data['personal_name'] ),
			'last_name'    => esc_attr( $data['personal_surname'] ),
			'phone_number' => esc_attr( $data['phone_number'] ),
			'role'         => esc_attr( 'customer' )
		];
		$user_id   = wp_insert_user( $user_data );
		if ( ! is_wp_error( $user_id ) ) {
			update_user_meta( $user_id, 'billing_company', $data['company_name'] );
			update_user_meta( $user_id, 'billing_first_name', $data['personal_name'] );
			update_user_meta( $user_id, 'billing_last_name', $data['personal_surname'] );
			update_user_meta( $user_id, 'billing_phone', $data['phone_number'] );
			update_user_meta( $user_id, 'billing_nip', $data['company_nip'] );
			update_user_meta( $user_id, 'subscribe_to_b2b', 1 );
			update_user_meta( $user_id, 'b2b_message', $message );

			wp_new_user_notification( $user_id, null, 'both' );

			return $user_id;
		}

		return null;
	}

	/**
	 * Updates user information and metadata based on provided data and message.
	 *
	 * @param array $data An associative array containing user data, including:
	 *                        'email_address' (string): The email address of the user.
	 *                        'personal_name' (string): The first name of the user.
	 *                        'personal_surname' (string): The last name of the user.
	 *                        'company_name' (string): The name of the company.
	 *                        'phone_number' (string): The user's phone number.
	 *                        'company_nip' (string): The company's NIP (tax identification number).
	 * @param string $message Additional message to associate with the user.
	 *
	 * @return int|null Returns the user ID if the update was successful, or null if the user does not exist or cannot be promoted.
	 */
	public function update_user( array $data, string $message ): ?int {
		$user = get_user_by( 'email', $data['email_address'] );
		if ( ! empty( $user ) ) {
			if ( $this->can_user_be_promoted( $user ) ) {
				wp_update_user( array(
					'ID'         => $user->ID,
					'first_name' => esc_attr( $data['personal_name'] ),
					'last_name'  => esc_attr( $data['personal_surname'] )
				) );
				update_user_meta( $user->ID, 'billing_company', $data['company_name'] );
				update_user_meta( $user->ID, 'billing_first_name', $data['personal_name'] );
				update_user_meta( $user->ID, 'billing_last_name', $data['personal_surname'] );
				update_user_meta( $user->ID, 'billing_phone', $data['phone_number'] );
				update_user_meta( $user->ID, 'billing_nip', $data['company_nip'] );
				update_user_meta( $user->ID, 'subscribe_to_b2b', 1 );
				update_user_meta( $user->ID, 'b2b_message', $message );

				return $user->ID;
			}
		}

		return null;
	}

	/**
	 * Denies a user by updating their subscription status to a restricted value.
	 *
	 * @param int|string|WP_User $user The user to deny. Accepts a user ID, email, or WP_User object.
	 * @param string $reason The reason for denying the user.
	 *
	 * @return bool Returns true if the user was successfully updated, false if the user could not be resolved or updated.
	 */
	public function deny_user( int|string|WP_User $user, string $reason ): bool {

		if ( empty( $user ) ) {
			return false;
		}

		if ( ! is_a( $user, WP_User::class ) ) {
			if ( is_string( $user ) ) {
				$user = get_user_by( 'email', $user );
			} elseif ( is_int( $user ) ) {
				$user = get_user_by( 'id', $user );
			}
		}

		if ( empty( $user ) ) {
			return false;
		}

		update_user_meta( $user->ID, 'subscribe_to_b2b', - 1 );

		return true;
	}

	/**
	 * Promotes the given user to a "b2b_client" role and updates their metadata.
	 *
	 * @param int|string|WP_User $user The user to promote, which can be specified by user ID, email address, or as a WP_User object.
	 *
	 * @return bool Returns true if the user was successfully promoted, false otherwise.
	 */
	public function promote_user( int|string|WP_User $user ): bool {

		if ( empty( $user ) ) {
			return false;
		}

		if ( ! is_a( $user, WP_User::class ) ) {
			if ( is_string( $user ) ) {
				$user = get_user_by( 'email', $user );
			} elseif ( is_int( $user ) ) {
				$user = get_user_by( 'id', $user );
			}
		}


		if ( ! $this->can_user_be_promoted( $user ) ) {
			return false;
		}

		$user->set_role( 'b2b_client' );
		update_user_meta( $user->ID, 'subscribe_to_b2b', 2 );


		return true;
	}

	/**
	 * Determines if a user is eligible for promotion based on their roles.
	 *
	 * @param mixed $user The user object or email address to evaluate for promotion eligibility.
	 *
	 * @return bool Returns true if the user can be promoted, false otherwise.
	 */
	public function can_user_be_promoted( mixed $user ): bool {
		if ( ! is_a( $user, WP_User::class ) ) {
			$user = get_user_by( 'email', $user );
		}

		if ( in_array( 'b2b_client', $user->roles ) ) {
			return false;
		}

		return true;
	}
}