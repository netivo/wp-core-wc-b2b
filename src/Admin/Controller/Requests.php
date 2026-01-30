<?php

namespace Netivo\Module\WooCommerce\B2B\Admin\Controller;

use Automattic\Jetpack\Connection\Package_Version;
use JetBrains\PhpStorm\NoReturn;
use Netivo\Module\WooCommerce\B2B\Admin\Controller\Requests as RequestsController;
use Netivo\Module\WooCommerce\B2B\Admin\Notice;
use Netivo\Module\WooCommerce\B2B\Admin\Table\Requests as RequestsTable;
use Netivo\Module\WooCommerce\B2B\Module;
use WP_Error;
use WP_User;

/**
 * Class Requests
 *
 * Represents the processing and management of B2B user requests.
 * This class provides functionality to display, accept, and deny requests,
 * including generation of administrative interfaces and handling related actions.
 */
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

    /**
     * Handles the display and actions for the list of user requests.
     *
     * This method initializes the request table and determines the current action.
     * Depending on the action, it performs the corresponding functionality:
     * - 'deny': Processes the denial logic and displays the deny form.
     * - 'accept': Processes the acceptance logic.
     * If no specific action is triggered, it displays the request table.
     *
     * @return void
     */
    public function list_users(): void {
        $this->list_table = new RequestsTable();
        switch ( $this->list_table->current_action() ) {
            case 'deny':
                $this->handle_deny();
                $this->show_deny_form();
                break;
            case 'accept':
                $this->handle_accept();
            default:
                $this->show_table();
        }
    }

    /**
     * Displays a table of B2B submissions in the admin interface.
     *
     * This method generates a page that includes a search box, filters, and a table
     * displaying the list of B2B submissions. It allows the user to search and
     * interact with the data. Notices can also be displayed above the table if any exist.
     * The table's data is prepared dynamically using the associated list table class.
     *
     * @return void
     */
    public function show_table(): void {
        $this->list_table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Zgłoszenia B2B', 'netivo' ); ?></h1>
            <hr class="wp-header-end">

            <?php Notice::display_notices(); ?>

            <?php $this->list_table->views(); ?>

            <form method="get">
                <input type="hidden" name="page" value="b2b-requests">
                <?php $this->list_table->search_box( __( 'Szukaj zgłoszenia', 'netivo' ), 'request' ); ?>

                <?php $this->list_table->display(); ?>

            </form>
        </div>
        <?php
    }

    /**
     * Handles the acceptance of a user request for promotion.
     *
     * This method validates the request, checks for the presence and validity of the user,
     * promotes the user if applicable, and provides feedback via notices.
     * It redirects the user to a predefined list URL after completing the operation.
     *
     * @return void
     */
    #[NoReturn]
    public function handle_accept(): void {
        check_admin_referer( 'accept-request' );

        if ( empty( $_GET['user'] ) ) {
            Notice::add( __( 'Wybierz zgłoszenie do zaakceptowania.', 'netivo' ), 'error' );
            wp_safe_redirect( admin_url( self::$list_url ) );
            exit;
        }

        $user = get_user_by( 'id', sanitize_text_field( $_GET['user'] ) );
        if ( empty( $user ) ) {
            Notice::add( __( 'Zgłoszenie o podanym ID nie istnieje.', 'netivo' ), 'error' );
            wp_safe_redirect( admin_url( self::$list_url ) );
            exit;
        }


        $res = Module::user_controller()->promote_user( $user );

        if ( $res ) {
            Notice::add( __( 'Użytkownik został zaakceptowany.', 'netivo' ), 'success' );
            wp_safe_redirect( admin_url( self::$list_url ) ); //@todo przekierowanie na reguły użytkownika
            exit;
        }
        Notice::add( __( 'Wystąpił błąd podczas akceptacji zgłoszenia. Powiązany użytkownik może mieć już rolę klienta B2B.', 'netivo' ),
                'error' );
        wp_safe_redirect( admin_url( self::$list_url ) );
        exit;

    }

    /**
     * Handles the denial of a user request.
     *
     * Validates the request for proper authorization and ensures a valid user ID is provided.
     * If the denial is confirmed and a message is provided, it attempts to deny the user request.
     * Adds appropriate notices and redirects the admin back to the list page depending on the outcome.
     *
     * @return void
     */
    public function handle_deny(): void {
        global $denied_user;
        check_admin_referer( 'deny-request' );

        if ( empty( $_GET['user'] ) ) {
            Notice::add( __( 'Wybierz zgłoszenie do odrzucenia.', 'netivo' ), 'error' );
            wp_safe_redirect( admin_url( self::$list_url ) );
            exit;
        }
        $user = get_user_by( 'id', sanitize_text_field( $_GET['user'] ) );
        if ( empty( $user ) ) {
            Notice::add( __( 'Zgłoszenie o podanym ID nie istnieje.', 'netivo' ), 'error' );
            wp_safe_redirect( admin_url( self::$list_url ) );
            exit;
        }

        $denied_user = $user;

        if ( ! empty( $_POST['confirm'] ) ) {
            if ( ! empty( $_POST['message'] ) ) {
                $message = sanitize_text_field( $_POST['message'] );

                $res = Module::user_controller()->deny_user( $user, $message );

                if ( $res ) {
                    Notice::add( __( 'Zgłoszenie zostało odrzucone.', 'netivo' ), 'success' );
                    wp_safe_redirect( admin_url( self::$list_url ) );
                    exit;
                }

                Notice::add( __( 'Wystąpił błąd podczas odrzucania zgłoszenia.', 'netivo' ), 'error' );
                wp_safe_redirect( admin_url( self::$list_url ) );
                exit;
            }
            Notice::add( __( 'Musisz podać powód odrzucenia.', 'netivo' ), 'error' );
        }

    }

    /**
     * Displays a form for denying a user request in the B2B submissions system.
     *
     * This method generates a form with a textarea to specify the reason for
     * denying the request. The data submitted through the form will be sent
     * to the deny URL and includes a nonce field for security purposes.
     *
     * @return void
     */
    public function show_deny_form(): void {
        global $denied_user;
        $deny_url = admin_url( RequestsController::$deny_url );
        $deny_url = add_query_arg( array( 'user' => $_GET['user'] ), $deny_url );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php echo sprintf( esc_html__( 'Zgłoszenia B2B - odrzucanie użytkownika %s', 'netivo' ),
                        $denied_user->user_email ); ?>
            </h1>
            <hr class="wp-header-end">
            <?php Notice::display_notices(); ?>
            <form action="<?php echo esc_url( $deny_url ); ?>" method="post">
                <table class="form-table" role="presentation">
                    <tr class="user-description-wrap">
                        <th><label for="message"><?php _e( 'Powód odrzucenia', 'netivo' ); ?></label></th>
                        <td>
                            <textarea name="message" id="message" rows="5" cols="30"></textarea>
                            <p class="description"><?php _e( 'Podaj powód odrzucenia zgłoszenia, ta wiadomość trafi do użytkownika, który się zgłaszał.', 'netivo' ); ?></p>
                        </td>
                    </tr>
                </table>
                <?php wp_nonce_field( 'deny-request' ); ?>
                <?php submit_button( __( 'Odrzuć zgłoszenie', 'netivo' ), 'primary', 'confirm' ); ?>
            </form>
        </div>
        <?php
    }
}