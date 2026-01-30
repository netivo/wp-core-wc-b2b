<?php

namespace Netivo\Module\WooCommerce\B2B\Admin\Controller;

use Netivo\Module\WooCommerce\B2B\Admin\Notice;
use Netivo\Module\WooCommerce\B2B\Admin\Table\Requests as RequestsTable;
use WP_Error;
use WP_User;

class Requests {
    public static string $list_url = 'admin.php?page=b2b-requests';
    public static string $accept_url = 'admin.php?page=b2b-requests&action=accept';
    public static string $deny_url = 'admin.php?page=b2b-requests&action=deny';
    protected RequestsTable $list_table;
    protected array $messages = [];
    protected ?WP_Error $errors = null;
    protected ?WP_User $current_user;
    protected array $user_fields = array();

    public function __construct() {
        $this->current_user = wp_get_current_user();
    }

    public function list_users(): void {
        $this->list_table = new RequestsTable();
        switch ( $this->list_table->current_action() ) {
            case 'accept':
                $this->handle_accept();
            default:
                $this->show_table();
        }
    }

    public function show_table(): void {
        $this->list_table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Zgłoszenia B2B', 'netivo' ); ?></h1>
            <hr class="wp-header-end">

            <?php Notice::display_notices(); ?>

            <?php $this->list_table->views(); ?>

            <form method="get">
                <input type="hidden" name="page" value="customers">
                <?php $this->list_table->search_box( __( 'Szukaj zgłoszenia', 'netivo' ), 'request' ); ?>

                <?php $this->list_table->display(); ?>

            </form>
        </div>
        <?php
    }

    public function handle_accept(): void {
        if ( empty( $_GET['user'] ) ) {
            Notice::add( __( 'Select user to accept.', 'netivo' ), 'error' );
            wp_safe_redirect( admin_url( self::$list_url ) );
            exit;
        }
    }
}