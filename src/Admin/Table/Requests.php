<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 * User: manveru
 * Date: 20.01.2026
 * Time: 13:51
 *
 */

namespace Netivo\Module\WooCommerce\B2B\Admin\Table;

use Netivo\Module\WooCommerce\B2B\Admin\Controller\Requests as RequestsController;
use Netivo\Module\WooCommerce\B2B\Admin\Model\Client;
use WP_List_Table;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class Requests extends WP_List_Table {

	public function __construct() {
		parent::__construct( array(
			'singular' => 'request',
			'plural'   => 'requests',
			'ajax'     => false
		) );
	}

	public function prepare_items(): void {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$search = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : ''; // @phpcs:ignore

		$users_per_page = $this->get_items_per_page( 'users_per_page' );
		$paged          = $this->get_pagenum();
		$orderby        = '';
		$order          = '';

		$res = Client::get_users( 'customer', $search, $users_per_page, $paged, $orderby, $order );

		$this->items = $res['items'];

		$this->set_pagination_args( array(
			'total_items' => $res['total'],
			'per_page'    => $users_per_page,
		) );
	}

	public function get_columns(): array {
		return array(
			'cb'           => '<input type="checkbox" />',
			'company_name' => 'Nazwa firmy',
			'nip'          => 'NIP',
			'name'         => 'Imię i nazwisko',
			'email'        => 'Email',
			'phone'        => 'Telefon',
			'message'      => 'Wiadomość',
			'date'         => 'Data rejestracji'
		);
	}

	protected function get_sortable_columns(): array {
		return array(
			'company_name' => array( 'company_name', true ),
			'nip'          => array( 'nip', false ),
			'name'         => array( 'last_name', false ),
			'email'        => array( 'user_email', false ),
			'phone'        => array( 'billing_phone', false ),
			'date'         => array( 'user_registered', false )
		);
	}

	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'date':
				return gmdate( 'd.m.Y H:i', strtotime( $item->user_registered ) );
			default:
				return '';
		}
	}

	protected function column_cb( $item ): string {
		return sprintf( '<input type="checkbox" name="users[]" value="%s" />', $item->ID );
	}

	protected function column_name( $item ): string {
		$full_name = trim( $item->first_name . ' ' . $item->last_name );

		return $full_name ?: $item->display_name;
	}

	protected function column_company_name( $item ): string {
		$company_name = get_user_meta( $item->ID, 'billing_company', true );
		$accept_url   = admin_url( RequestsController::$accept_url . '&user=' . $item->ID );
		$accept_url   = add_query_arg( array( 'user_id' => $item->ID ), $accept_url );
		$deny_url     = admin_url( RequestsController::$deny_url . '&user=' . $item->ID );
		$deny_url     = add_query_arg( array( 'user_id' => $item->ID ), $deny_url );

		$actions = array(
			'accept' => sprintf( '<a href="%s">Zaakceptuj</a>', esc_url( $accept_url ) ),
			'deny'   => sprintf( '<a href="%s">Odrzuć</a>', esc_url( $deny_url ) )
		);

		return sprintf( '<strong>%s</strong>%s', esc_html( $company_name ), $this->row_actions( $actions ) );
	}

	protected function column_nip( $item ): string {
		$nip = get_user_meta( $item->ID, 'billing_nip', true );

		return $nip;
	}

	protected function column_email( $item ): string {
		return sprintf( '<a href="mailto:%s">%s</a>', esc_attr( $item->user_email ), esc_html( $item->user_email ) );
	}

	protected function column_phone( $item ): string {
		$phone = get_user_meta( $item->ID, 'billing_phone', true );

		return sprintf( '<a href="tel:%s">%s</a>', esc_attr( $phone ), esc_html( $phone ) );
	}

	protected function column_message( $item ): string {
		$message = get_user_meta( $item->ID, 'b2b_message', true );

		return esc_html( $message );
	}
}