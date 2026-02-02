<?php

namespace Netivo\Module\WooCommerce\B2B\Admin\Controller;

use Netivo\Core\Database\EntityManager;
use Netivo\Module\WooCommerce\B2B\Admin\Controller\Clients as ClientsController;
use Netivo\Module\WooCommerce\B2B\Admin\Notice;
use Netivo\Module\WooCommerce\B2B\Admin\Table\Clients as ClientsTable;
use Netivo\Module\WooCommerce\B2B\Model\Discount;
use Netivo\Module\WooCommerce\B2B\Module;
use WP_Error;
use WP_Term_Query;
use WP_User;

class Clients {
    public static string $list_url = 'admin.php?page=b2b-clients';
    public static string $rules_url = 'admin.php?page=b2b-clients&action=rules';
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
            case 'rules':
                $this->handle_rule_add();
                $this->show_rules();
                break;
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
                <?php $this->list_table->search_box( __( 'Szukaj klienta', 'netivo' ), 'client' ); ?>

                <?php $this->list_table->display(); ?>

            </form>
        </div>
        <?php
    }

    public function handle_rule_add(): void {
        global $b2b_user;

        if ( empty( $_GET['user'] ) ) {
            Notice::add( __( 'Wybierz użytkownika do edycji reguł.', 'netivo' ), 'error' );
            wp_safe_redirect( admin_url( self::$list_url ) );
            exit;
        }
        $user = get_user_by( 'id', sanitize_text_field( $_GET['user'] ) );
        if ( empty( $user ) ) {
            Notice::add( __( 'Użytkownik o podanym ID nie istnieje.', 'netivo' ), 'error' );
            wp_safe_redirect( admin_url( self::$list_url ) );
            exit;
        }

        if ( Module::user_controller()->is_user_b2b_client( $user ) === false ) {
            Notice::add( __( 'Użytkownik nie jest klientem B2B.', 'netivo' ), 'error' );
            wp_safe_redirect( admin_url( self::$list_url ) );
            exit;
        }

        $b2b_user = $user;

        if ( ! empty( $_POST['add-rule'] ) ) {
            check_admin_referer( 'add-b2b-rule' );

            if ( empty( $_POST['type'] ) || empty( $_POST['price_type'] ) || empty( $_POST['value'] ) ) {
                Notice::add( __( 'Wypełnij wszystkie pola.', 'netivo' ), 'error' );
            } else {

                $type       = sanitize_text_field( $_POST['type'] );
                $price_type = sanitize_text_field( $_POST['price_type'] );
                $value      = sanitize_text_field( $_POST['value'] );

                if ( empty( $_POST[ $type ] ) ) {
                    Notice::add( __( 'Wybierz produkt lub kategorię do nadania rabatu', 'netivo' ) );
                } else {
                    $object_id = sanitize_text_field( $_POST[ $type ] );

                    $discount             = new Discount();
                    $discount->type       = $type;
                    $discount->price_type = $price_type;
                    $discount->value      = $value;
                    $discount->user_id    = $b2b_user->ID;
                    $discount->type_id    = $object_id;

                    try {
                        EntityManager::save( $discount );

                        Notice::add( __( 'Reguła została dodana.', 'netivo' ), 'success' );
                    } catch ( \Exception $e ) {
                        Notice::add( __( 'Nnie udało się nadać rabatu', 'netivo' ) );
                    }
                    $rules_url = admin_url( self::$rules_url );
                    $rules_url = add_query_arg( array( 'user' => $b2b_user->ID ), $rules_url );
                    wp_safe_redirect( esc_url( $rules_url ) );
                    exit;
                }
            }

        }
    }

    public function show_rules(): void {
        global $b2b_user;
        $user_company = get_user_meta( $b2b_user->ID, 'billing_company', true );
        $user_nip     = get_user_meta( $b2b_user->ID, 'billing_nip', true );

        $category_rules = Discount::get_discounts_for_user( $b2b_user->ID, 'category' );
        $product_rules  = Discount::get_discounts_for_user( $b2b_user->ID, 'product' );

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php echo sprintf( esc_html__( 'Reguły cenowe B2B - użytkownik %s (%s)', 'netivo' ), $user_company, $user_nip ); ?>
            </h1>
            <hr class="wp-header-end">
            <div id="col-container" class="wp-clearfix">
                <div id="col-left">
                    <div class="col-wrap">
                        <h2><?php echo esc_html__( 'Dodaj regułę' ); ?></h2>
                        <?php $this->print_add_rule_form(); ?>
                    </div>
                </div>
                <div id="col-right">
                    <div class="col-wrap">
                        <h2><?php echo esc_html__( 'Reguły kategorii' ); ?></h2>
                        <?php $this->print_rules_list( $category_rules ); ?>

                        <h2><?php echo esc_html__( 'Reguły produktowe' ); ?></h2>
                        <?php $this->print_rules_list( $product_rules ); ?>
                    </div>
                </div>
            </div>

        </div>
        <?php
    }

    protected function print_add_rule_form(): void {
        global $b2b_user;
        $categories = new WP_Term_Query( array(
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'exclude'    => Discount::get_discounts_for_user( $b2b_user->ID, 'category', 'ids' )
        ) );

        $form_action = admin_url( self::$rules_url );
        $form_action = add_query_arg( array( 'user' => $b2b_user->ID ), $form_action );

        if ( file_exists( Module::get_module_path() . '/views/admin/form/add-rule.php' ) ) {
            include Module::get_module_path() . '/views/admin/form/add-rule.php';
        }
    }

    protected function print_rules_list( $rules ): void {
        ?>
        <table class="wp-list-table widefat fixed striped table-view-list users" style="margin-bottom: 2rem">
            <thead>
            <tr>
                <th>Nazwa elementu</th>
                <th>Rabat</th>
                <th>Akcja</th>
            </tr>
            </thead>
            <tbody>
            <?php if ( ! empty( $rules ) ) : ?>
                <?php foreach ( $rules as $rule ): ?>
                    <tr>
                        <td><?php echo esc_html( $rule['name'] ); ?></td>
                        <td>
                            <?php echo esc_html( $rule['value'] ); ?>
                            <?php echo ( $rule['price_type'] == 'percent' ) ? '%' : get_woocommerce_currency() ?>
                        </td>
                        <td>
                            <a href="#" class="button button-secondary">
                                <?php echo esc_html__( 'Usuń', 'netivo' ); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="3"><?php echo esc_html__( 'Brak reguł.', 'netivo' ); ?></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
}