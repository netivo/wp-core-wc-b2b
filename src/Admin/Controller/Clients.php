<?php

namespace Netivo\Module\WooCommerce\B2B\Admin\Controller;

use Netivo\Module\WooCommerce\B2B\Admin\Table\Clients as ClientsTable;
use WP_Error;
use WP_User;

class Clients {
    public static string $list_url = 'admin.php?page=clients';
    public static string $rules_url = 'admin.php?page=customers&action=rules';
    protected ClientsTable $list_table;
    protected array $messages = [];
    protected ?WP_Error $errors = null;
    protected ?WP_User $current_user;
    protected array $user_fields = array();

    public function __construct() {
        $this->current_user = wp_get_current_user();
    }

    public function list_users(): void {
        $this->list_table = new ClientsTable();
        switch ( $this->list_table->current_action() ) {
            default:
                $this->show_table();
        }
    }

    public function show_table(): void {
        $this->list_table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Klienci B2B', 'netivo' ); ?></h1>
            <hr class="wp-header-end">

            <?php $this->list_table->views(); ?>

            <form method="get">
                <input type="hidden" name="page" value="customers">
                <?php $this->list_table->search_box( __( 'Szukaj klienta', 'netivo' ), 'user' ); ?>

                <?php $this->list_table->display(); ?>

            </form>
        </div>
        <?php
    }
}