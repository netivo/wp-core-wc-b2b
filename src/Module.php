<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 * User: manveru
 * Date: 20.01.2026
 * Time: 11:41
 *
 */

namespace Netivo\Module\WooCommerce\B2B;

use Netivo\Core\Database\EntityManager;
use Netivo\Module\WooCommerce\B2B\Admin\Panel;
use Netivo\Module\WooCommerce\B2B\Controller\Product;
use Netivo\Module\WooCommerce\B2B\Woocommerce\Gateway\Proforma;
use Netivo\Module\WooCommerce\B2B\Controller\User as UserController;
use Netivo\Module\WooCommerce\B2B\Gutenberg\RegisterForm as RegisterFormBlock;
use Netivo\Module\WooCommerce\B2B\Model\Discount as DiscountModel;
use Netivo\Module\WooCommerce\B2B\Rest\Form;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

/**
 * Represents the core module class, providing a singleton instance for managing
 * the module's functionality and associated components such as the UserController.
 */
class Module {

	/**
	 * Holds the instance of the class or object, initialized to null.
	 */
	protected static ?self $instance = null;

	/**
	 *
	 */
	protected UserController $userController;

	/**
	 * Cached default rules loaded from config file. Null means not yet loaded.
	 */
	protected static ?array $default_rules_config = null;

	/**
	 * Retrieves the singleton instance of the class.
	 *
	 * @return self Returns the single instance of the class.
	 */
	public static function get_instance(): self {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Retrieves the instance of the UserController.
	 *
	 * @return UserController The instance of the UserController.
	 */
	public static function user_controller(): UserController {
		return self::get_instance()->get_user_controller();
	}

	/**
	 * Determines if the current context is a B2B (Business-to-Business) context.
	 *
	 * This method checks various conditions such as query variables, request URIs,
	 * HTTP referer headers, and GET parameters to identify if the current context
	 * is related to B2B.
	 *
	 * @return bool True if the current context is identified as B2B, otherwise false.
	 */
	public static function is_b2b_context(): bool {
		$var = get_query_var( 'b2b' );

		if ( ! empty( $var ) ) {
			return true;
		}

		$b2b_base_url = get_option( 'nt_b2b_base_url', 'panel-b2b' );

		if ( empty( $b2b_base_url ) ) {
			$b2b_base_url = 'panel-b2b';
		}

		if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
			if ( isset( $_SERVER['HTTP_REFERER'] ) && str_contains( (string) $_SERVER['HTTP_REFERER'], (string) $b2b_base_url ) ) {
				return true;
			}
		}

		if ( isset( $_SERVER['REQUEST_URI'] ) && $_SERVER['REQUEST_URI'] !== '' && str_contains( (string) $_SERVER['REQUEST_URI'], (string) $b2b_base_url ) ) {
			return true;
		}

		if ( isset( $_GET['b2b'] ) && $_GET['b2b'] == 1 ) {
			return true;
		}

		return false;
	}

	/**
	 * Determines if the current user has the B2B client role.
	 *
	 * @return bool True if the current user is a B2B client, otherwise false.
	 */
	public static function is_b2b_user(): bool {
		$user = wp_get_current_user();
		if ( empty( $user->ID ) ) {
			return false;
		}

		return self::user_controller()->is_user_b2b_client( $user );
	}

	/**
	 * Initializes the constructor for the class.
	 *
	 * This method sets up the necessary components, including the UserController,
	 * Rewrite object, and database table creation for the DiscountModel. Additionally,
	 * it initializes the admin Panel if the current environment is in the admin context.
	 *
	 * @return void
	 */
	protected function __construct() {

		add_action( 'wp_enqueue_scripts', [ $this, 'style_and_script' ], 5 );
		add_action( 'widgets_init', [ $this, 'add_sidebar' ], 100 );
		add_filter( 'body_class', [ $this, 'add_b2b_class' ] );
		add_action( 'template_redirect', [ $this, 'redirect_non_b2b_users' ] );
		add_action( 'template_redirect', [ $this, 'redirect_b2b_users_to_panel' ] );
		add_action( 'template_redirect', [ $this, 'product_redirect_to_b2b' ] );
		add_action( 'template_redirect', [ $this, 'redirect_category_to_b2b' ] );
		add_action( 'template_redirect', [ $this, 'redirect_b2b_users_to_b2b_checkout' ] );
		add_filter( 'loop_shop_per_page', [ $this, 'b2b_products_per_page' ], 20 );
		add_filter( 'woocommerce_account_menu_items', [ $this, 'b2b_custom_menu_items' ], 10, 1 );
		add_filter( 'woocommerce_get_endpoint_url', [ $this, 'override_panel_b2b_endpoint_url' ], 10, 4 );
		add_filter( 'pre_option_woocommerce_enable_ajax_add_to_cart', [ $this, 'enable_ajax_add_to_cart_for_b2b' ] );

		add_filter( 'netivo/b2b/override_desktop_header', [ $this, 'override_desktop_header' ] );
		add_filter( 'woocommerce_page_title', [ $this, 'filter_b2b_page_title' ] );
		add_filter( 'the_title', [ $this, 'filter_b2b_shop_page_title' ], 10, 2 );
		add_filter( 'woocommerce_get_breadcrumb', [ $this, 'fix_woocommerce_breadcrumb' ], 10, 2 );
		add_action( 'woocommerce_before_checkout_form', [ $this, 'add_back_to_panel_button' ], 20 );
		add_action( 'woocommerce_before_cart', [ $this, 'add_back_to_panel_button' ], 20 );

		add_filter('woocommerce_get_cart_url', [$this, 'change_cart_checkout_url']);
		add_filter('woocommerce_get_checkout_url', [$this, 'change_cart_checkout_url']);
		add_filter('woocommerce_return_to_shop_redirect', [$this, 'change_shop_redirect_url']);
		add_filter('woocommerce_return_to_shop_text', [$this, 'change_shop_return_text']);

		add_action('init', function(){
			if(self::is_b2b_context()) {
				add_filter('netivo/woocommerce/fv/name-required', '__return_true');
				add_filter('netivo/woocommerce/fv/hide-name', '__return_false');
			}
		});

		$this->userController = new UserController();
		new Product();
		new Woocommerce\Product();
		new Woocommerce\Shipping();
		new Woocommerce\Invoice();
		new Woocommerce\Order();

		add_filter( 'woocommerce_payment_gateways', [ $this, 'register_proforma_gateway' ] );
		add_filter( 'woocommerce_available_payment_gateways', [ $this, 'filter_payment_gateways' ] );
		new Rewrite();
		new RegisterFormBlock();
		EntityManager::createTable( DiscountModel::class );
		new Emails();

		new Form();

		$this->register_role();

		if ( is_admin() ) {
			new Panel();
		}

		$this->remove_omnibus();
	}


	/**
	 * Gets the UserController instance associated with this object.
	 *
	 * @return UserController The instance of the UserController.
	 */
	public function get_user_controller(): UserController {
		return $this->userController;
	}

	/**
	 * Retrieves the file system path of the module directory.
	 *
	 * @return false|string|null Returns the absolute path to the module directory if it exists,
	 *                           false if the path cannot be resolved, or null if the file does not exist.
	 */
	public static function get_module_path(): false|string|null {
		$file = realpath( __DIR__ . '/../' );
		if ( file_exists( $file ) ) {
			return $file;
		}

		return null;
	}

	/**
	 * Retrieves the URI of the module.
	 *
	 * @return false|string|null The module URI if available, false on failure, or null if the module path is empty.
	 */
	public static function get_module_uri(): false|string|null {
		$path = Module::get_module_path();
		if ( ! empty( $path ) ) {
			$td   = get_template_directory();
			$turl = get_template_directory_uri();

			return str_replace( $td, $turl, $path );
		}

		return null;
	}

	/**
	 * Registers a role and sets up initialization if the role exists.
	 *
	 * @return void
	 */
	protected function register_role(): void {
		$role_exists = get_option( 'nt_b2b_role_exists' );
		if ( empty( $role_exists ) ) {
			add_action( 'init', array( $this, 'init_role' ) );
		}
	}

	/**
	 * Initializes the "b2b_client" role by duplicating the capabilities of the existing "customer" role.
	 * If the "customer" role exists, it creates the "b2b_client" role with the same capabilities
	 * and sets an option to indicate the role's existence.
	 *
	 * @return void
	 */
	public function init_role(): void {
		$customer = get_role( 'customer' );
		if ( null !== $customer ) {
			$capabilities = $customer->capabilities;
			add_role( 'b2b_client', __( 'Klient B2B', 'netivo' ), $capabilities );
			add_option( 'nt_b2b_role_exists', 1 );
		}
	}

	public function style_and_script() {
		if ( Module::is_b2b_context() ) {

			wp_enqueue_style( 'nt-b2b-style', self::get_module_uri() . '/dist/netivo-b2b-archive.css' );

			$popup_handle = 'nt-b2b-popup';
			wp_register_script( $popup_handle, self::get_module_uri() . '/dist/netivo-b2b-archive.js', [], null, true );
			wp_enqueue_script( $popup_handle );

			wp_localize_script( $popup_handle, 'b2b_popup_vars', [
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'security' => wp_create_nonce( 'b2b_popup_nonce' ),
				'language' => defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : '',
				'currency' => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '',
			] );

			wp_dequeue_script( 'wc-add-to-cart' );
			wp_deregister_script( 'wc-add-to-cart' );
		}
	}


	public function add_sidebar() {
		register_sidebar( array(
			'name'          => __( 'Sidebar B2B', 'netivo' ),
			'id'            => 'sidebar-b2b',
			'description'   => __( 'Widgety wyświetlane w panelu B2B.', 'netivo' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s sidebar-b2b__widget">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="sidebar-b2b__title">',
			'after_title'   => '</h3>',
		) );

	}


	public function add_b2b_class( $classes ) {
		if ( Module::is_b2b_context() ) {
			$classes[] = 'woocommerce--b2b';
		}

		return $classes;
	}

	public function b2b_products_per_page( int $cols ): int {
		if ( Module::is_b2b_context() ) {
			return 50;
		}

		return $cols;
	}

	public function redirect_b2b_users_to_panel(): void {
		if ( ! has_block( 'netivo/register-form' ) ) {
			return;
		}

		if ( self::is_b2b_user() ) {
			$b2b_base = get_option( 'nt_b2b_base_url', 'panel-b2b' );
			wp_safe_redirect( home_url( '/' . $b2b_base . '/' ) );
			exit;
		}
	}

	public function redirect_non_b2b_users(): void {
		if ( ! Module::is_b2b_context() ) {
			return;
		}

		if ( current_user_can( 'b2b_client' ) ) {
			return;
		}

		$user = wp_get_current_user();
		if ( ! Module::user_controller()->is_user_b2b_client( $user ) ) {
			wp_safe_redirect( home_url() );
			exit;
		}
	}

	/**
	 * Registers the Proforma gateway class with WooCommerce.
	 *
	 * @param array $gateways Registered gateway class names.
	 * @return array
	 */
	public function register_proforma_gateway( array $gateways ): array {
		$gateways[] = Proforma::class;

		return $gateways;
	}

	/**
	 * Filters available payment gateways at checkout based on B2B status:
	 *  - B2B users see only the Proforma gateway.
	 *  - B2C users never see the Proforma gateway.
	 *
	 * @param array $gateways Instantiated, available gateway objects.
	 * @return array
	 */
	public function filter_payment_gateways( array $gateways ): array {
		$is_b2b = self::is_b2b_context();

		foreach ( $gateways as $id => $gateway ) {
			if ( $is_b2b && $id !== Proforma::ID ) {
				unset( $gateways[ $id ] );
			} elseif ( ! $is_b2b && $id === Proforma::ID ) {
				unset( $gateways[ $id ] );
			}
		}

		return $gateways;
	}

	public function remove_omnibus() {
		if ( Module::is_b2b_context() ) {
			add_filter( 'iworks_omnibus_show', '__return_false' );
		}
	}

	function override_panel_b2b_endpoint_url( $url, $endpoint, $value, $permalink ) {
		if ( $endpoint === 'panel-b2b' ) {
			return home_url( '/panel-b2b/' );
		}

		return $url;
	}


	public function b2b_custom_menu_items( $items ) {
		if ( Module::is_b2b_user() ) {

			$custom_items = array(
				'panel-b2b' => __( 'Strefa B2B', 'netivo' )
			);

			$position = 1;

			return array_slice( $items, 0, $position, true ) + $custom_items
			       + array_slice( $items, $position, null, true );

		} else {
			return $items;
		}
	}

	public function enable_ajax_add_to_cart_for_b2b( $value ) {
		if ( self::is_b2b_user() ) {
			return 'yes';
		}

		return $value;
	}

	public function override_desktop_header( bool $override ): bool {
		if ( self::is_b2b_context() ) {
			include self::get_module_path() . '/woocommerce/b2b/header.php';

			return true;
		}

		return $override;
	}

	public function filter_b2b_page_title( string $title ): string {
		if ( self::is_b2b_context() ) {
			return __( 'Panel B2B', 'netivo' );
		}

		return $title;
	}

	public function filter_b2b_shop_page_title( string $title, $post_id ): string {
		if ( self::is_b2b_context() && (int) $post_id === (int) get_option( 'woocommerce_shop_page_id' ) ) {
			return __( 'Panel B2B', 'netivo' );
		}

		return $title;
	}

	public function add_back_to_panel_button(): void {
		if ( ! self::is_b2b_context() ) {
			return;
		}

		$b2b_base = get_option( 'nt_b2b_base_url', 'panel-b2b' );
		$b2b_url  = home_url( '/' . $b2b_base . '/' );

		echo '<a href="' . esc_url( $b2b_url ) . '" class="button b2b-back-to-panel"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M18.75 9.99994C18.75 10.1657 18.6842 10.3247 18.5669 10.4419C18.4497 10.5591 18.2908 10.6249 18.125 10.6249L3.38375 10.6249L7.3175 14.5574C7.43486 14.6748 7.50079 14.834 7.50079 14.9999C7.50079 15.1659 7.43486 15.3251 7.3175 15.4424C7.20014 15.5598 7.04097 15.6257 6.875 15.6257C6.70903 15.6257 6.54986 15.5598 6.4325 15.4424L1.4325 10.4424C1.3743 10.3844 1.32812 10.3154 1.29661 10.2395C1.2651 10.1636 1.24888 10.0822 1.24888 9.99994C1.24888 9.91773 1.2651 9.83633 1.29661 9.7604C1.32812 9.68447 1.3743 9.6155 1.4325 9.55744L6.4325 4.55744C6.54986 4.44008 6.70903 4.37415 6.875 4.37415C7.04097 4.37415 7.20014 4.44008 7.3175 4.55744C7.43486 4.6748 7.50079 4.83397 7.50079 4.99994C7.50079 5.16591 7.43486 5.32508 7.3175 5.44244L3.38375 9.37494L18.125 9.37494C18.2908 9.37494 18.4497 9.44079 18.5669 9.558C18.6842 9.67521 18.75 9.83418 18.75 9.99994Z" fill="#000"/></svg>' . esc_html__( 'Wróć do panelu', 'netivo' ) . '</a>';
	}

	public function fix_woocommerce_breadcrumb( array $crumbs, \WC_Breadcrumb $breadcrumb ): array {

		if ( ! self::is_b2b_context() ) {
			return $crumbs;
		}

		$b2b_base = get_option( 'nt_b2b_base_url', 'panel-b2b' );

		foreach ( $crumbs as &$crumb ) {
			$crumb[1] = str_replace( '/' . get_post_field( 'post_name', wc_get_page_id( 'shop' ) ) . '/', '', $crumb[1] );
		}

		return $crumbs;
	}

	public function product_redirect_to_b2b(): void {
		if ( ! is_product() || ! self::is_b2b_user() ) {
			return;
		}

		$b2b_base = get_option( 'nt_b2b_base_url', 'panel-b2b' );
		$product  = wc_get_product( get_the_ID() );
		$sku      = $product ? $product->get_sku() : '';

		if ( ! empty( $sku ) ) {
			$sku = str_replace( '&', '%26', $sku );
			wp_safe_redirect( home_url( '/' . $b2b_base . '/?s=' . $sku . '&post_type=product&b2b=1' ) );
		} else {
			wp_safe_redirect( home_url( '/' . $b2b_base . '/' ) );
		}
		exit();
	}

	public function redirect_category_to_b2b(): void {
		if ( ! is_product_category() || self::is_b2b_context() || ! self::is_b2b_user() ) {
			return;
		}

		$b2b_base  = get_option( 'nt_b2b_base_url', 'panel-b2b' );
		$term_link = get_term_link( get_queried_object() );

		if ( ! is_wp_error( $term_link ) ) {
			$path = wp_parse_url( $term_link, PHP_URL_PATH );
			wp_safe_redirect( home_url( '/' . $b2b_base . $path ) );
			exit();
		}
	}

	public function redirect_b2b_users_to_b2b_checkout(): void {
		if ( self::is_b2b_context() || ! self::is_b2b_user() ) {
			return;
		}

		if ( ! is_cart() && ! is_checkout() ) {
			return;
		}

		$b2b_base      = get_option( 'nt_b2b_base_url', 'panel-b2b' );
		$checkout_page = get_post( get_option( 'woocommerce_checkout_page_id' ) );

		wp_safe_redirect( home_url( '/' . $b2b_base . '/' . $checkout_page->post_name . '/' ) );
		exit();
	}

	public function change_cart_checkout_url($url): string {
		if(self::is_b2b_context()) {
			$b2b_base = get_option( 'nt_b2b_base_url', 'panel-b2b' );
			return str_replace( site_url(), site_url().'/'.$b2b_base, $url );
		}
		return $url;
	}

	public function change_shop_redirect_url($url): string {
		if(self::is_b2b_context()) {
			$b2b_base = get_option( 'nt_b2b_base_url', 'panel-b2b' );
			return home_url( '/' . $b2b_base . '/' );
		}
		return $url;
	}

	public function change_shop_return_text($url): string {
		if(self::is_b2b_context()) {
			return __('Powróć do panelu B2B', 'netivo');
		}
		return $url;
	}

	/**
	 * Lazy-loads the default B2B discount rules from the theme config file.
	 * The file is read only once per request; subsequent calls return the cached result.
	 *
	 * @return array<int, array{type: string, identifier: string, price_type: string, value: string}>
	 */
	private static function load_default_rules_config(): array {
		if ( self::$default_rules_config === null ) {
			$config_path = get_template_directory() . '/config/b2b-default-rules.config.php';
			if ( file_exists( $config_path ) ) {
				self::$default_rules_config = (array) require $config_path;
			} else {
				self::$default_rules_config = [];
			}
		}

		return self::$default_rules_config;
	}

	/**
	 * Builds a list of Discount objects for the given user using a named rule set from config.
	 * Rules whose identifier cannot be resolved to an existing term or product are skipped.
	 * The returned objects are ready to be persisted via EntityManager::save().
	 *
	 * @param int    $user_id WP user ID of the B2B client.
	 * @param string $set     Key of the rule set defined in b2b-default-rules.config.php.
	 * @return DiscountModel[]
	 */
	public static function create_default_discount_rules_for_user( int $user_id, string $set = 'standard' ): array {
		$config = self::load_default_rules_config();

		if ( empty( $config[ $set ]['rules'] ) ) {
			return [];
		}

		$discounts = [];

		foreach ( $config[ $set ]['rules'] as $rule ) {
			if ( empty( $rule['type'] ) || empty( $rule['identifier'] ) || empty( $rule['price_type'] ) || $rule['value'] === '' ) {
				continue;
			}

			$type_id = null;

			switch ( $rule['type'] ) {
				case 'category':
					$term = get_term_by( 'slug', $rule['identifier'], 'product_cat' );
					if ( $term && ! is_wp_error( $term ) ) {
						$type_id = $term->term_id;
					}
					break;

				case 'brand':
					$term = get_term_by( 'slug', $rule['identifier'], 'product_brand' );
					if ( $term && ! is_wp_error( $term ) ) {
						$type_id = $term->term_id;
					}
					break;

				case 'product':
					$product_id = wc_get_product_id_by_sku( $rule['identifier'] );
					if ( ! empty( $product_id ) ) {
						$type_id = $product_id;
					}
					break;
			}

			if ( $type_id === null ) {
				continue;
			}

			$discount             = new DiscountModel();
			$discount->user_id    = $user_id;
			$discount->type       = $rule['type'];
			$discount->type_id    = (int) $type_id;
			$discount->price_type = $rule['price_type'];
			$discount->value      = (string) $rule['value'];

			$discounts[] = $discount;
		}

		return $discounts;
	}

}