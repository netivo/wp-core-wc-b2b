<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 * User: manveru
 * Date: 30.01.2026
 * Time: 11:20
 *
 */

namespace Netivo\Module\WooCommerce\B2B\Admin;


if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class Notice {

	public static function add( $message, $type = 'error' ): void {
		self::check_session();
		$notices = array();
		if ( array_key_exists( 'netivo_notices', $_SESSION ) ) {
			$notices = $_SESSION['netivo_notices'];
		}
		$notices[]                  = array( 'message' => $message, 'type' => $type );
		$_SESSION['netivo_notices'] = $notices;
	}

	public static function display_notices(): void {
		self::check_session();
		$notices = array();
		if ( array_key_exists( 'netivo_notices', $_SESSION ) ) {
			$notices = $_SESSION['netivo_notices'];
		}
		$errors = array();
		foreach ( $notices as $notice ) {
			if ( $notice['type'] == 'error' ) {
				$errors[] = $notice['message'];
			} else {
				wp_admin_notice( $notice['message'], array( 'type' => $notice['type'], 'dismissible' => true, ) );
			}
		}

		if ( ! empty( $errors ) ) {
			$errors = array_map( function ( $error ) {
				return '<li>' . $error . '</li>';
			}, $errors );

			$message = '<ul>' . implode( '', $errors ) . '</ul>';

			wp_admin_notice( $message, array( 'type' => 'error', 'dismissible' => true ) );
		}

		unset( $_SESSION['netivo_notices'] );
	}


	protected static function check_session(): void {
		if ( ! session_id() ) {
			session_start();
		}
	}
}