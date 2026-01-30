<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 * User: manveru
 * Date: 30.01.2026
 * Time: 13:10
 *
 */

namespace Netivo\Module\WooCommerce\B2B;

use Netivo\Module\WooCommerce\B2B\Woocommerce\Email\RequestAccepted;
use Netivo\Module\WooCommerce\B2B\Woocommerce\Email\RequestDenied;
use Netivo\Module\WooCommerce\B2B\Woocommerce\Email\RequestSent;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class Emails {


	public function __construct() {
		add_filter( 'woocommerce_email_classes', [ $this, 'custom_emails' ] );
		add_filter( 'woocommerce_email_actions', array( $this, 'my_email_actions' ), 10, 1 );
	}

	public function custom_emails( $emails ) {
		$emails['WC_Email_Customer_B2B_Request_Sent']     = new RequestSent();
		$emails['WC_Email_Customer_B2B_Request_Accepted'] = new RequestAccepted();
		$emails['WC_Email_Customer_B2B_Request_Denied']   = new RequestDenied();

		return $emails;
	}

	public function my_email_actions( $actions ) {
		$actions[] = 'nt_b2b_request_sent';
		$actions[] = 'nt_b2b_request_accepted';
		$actions[] = 'nt_b2b_request_denied';

		return $actions;
	}
}