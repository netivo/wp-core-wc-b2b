<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 * User: manveru
 * Date: 20.01.2026
 * Time: 13:51
 *
 */

namespace Netivo\Module\WooCommerce\B2B\Admin\Table;

use Netivo\Module\WooCommerce\B2B\Admin\Model\Client;
use WP_List_Table;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class Clients extends WP_List_Table {

	public function __construct() {
		parent::__construct( array(
			'singular' => 'client',
			'plural'   => 'clients',
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

		$res = Client::get_users( 'b2b_client', $search, $users_per_page, $paged, $orderby, $order );

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
			'date'         => 'Data rejestracji'
		);
	}

	protected function get_sortable_columns(): array {
		return array(
			'company_name' => array( 'company_name', true ),
			'nip'          => array( 'nip', false ),
			'name'         => array( 'display_name', false ),
			'email'        => array( 'user_email', false ),
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

		$actions = array(
			'rules' => sprintf( '<a href="%s">Reguły cenowe</a>', esc_url( admin_url() ) )
		);

		return sprintf( '<strong><a href="%s">%s</a></strong>%s', esc_url( admin_url() ), esc_html( $company_name ), $this->row_actions( $actions ) );
	}

	protected function column_nip( $item ): string {
		$nip = get_user_meta( $item->ID, 'billing_nip', true );

		return $nip;
	}

	protected function column_email( $item ): string {
		return sprintf( '<a href="mailto:%s">%s</a>', esc_attr( $item->user_email ), esc_html( $item->user_email ) );
	}
}