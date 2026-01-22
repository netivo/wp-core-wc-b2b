<?php

namespace Netivo\Module\WooCommerce\B2B\Admin;

use Netivo\Module\WooCommerce\B2B\Admin\Controller\Clients as ClientsController;
use Netivo\Module\WooCommerce\B2B\Admin\Controller\Requests as RequestsController;

class Menu {

	protected ClientsController $clients_controller;
	protected RequestsController $requests_controller;

	public function __construct() {
		$this->clients_controller  = new ClientsController();
		$this->requests_controller = new RequestsController();
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
	}

	public function add_admin_menu(): void {
		global $menu;
		$menu[39] = array( // @phpcs:ignore
			'',
			'read',
			'separator-sales',
			'',
			'wp-menu-separator'
		);

		add_menu_page(
			'Klienci B2B',
			'Klienci B2B',
			'manage_woocommerce',
			'b2b-clients',
			array( $this->clients_controller, 'list_users' ),
			'dashicons-businessman',
			40
		);

		add_submenu_page(
			'b2b-clients',
			'Zgłoszenia',
			'Zgłoszenia',
			'manage_woocommerce',
			'b2b-requests',
			array( $this->requests_controller, 'list_users' )
		);
	}
}