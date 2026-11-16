<?php

namespace Netivo\Module\WooCommerce\B2B\Tests\Woocommerce;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Netivo\Module\WooCommerce\B2B\Woocommerce\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase {

	private bool $is_admin = false;
	private bool $doing_ajax = false;
	private array $added_filters = [];

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		$this->is_admin      = false;
		$this->doing_ajax    = false;
		$this->added_filters = [];
		unset( $_SERVER['HTTP_REFERER'], $_GET['b2b'] );
		$_SERVER['REQUEST_URI'] = '/';

		Functions\when( 'is_admin' )->alias( fn() => $this->is_admin );
		Functions\when( 'wp_doing_ajax' )->alias( fn() => $this->doing_ajax );
		Functions\when( 'get_query_var' )->justReturn( '' );
		Functions\when( 'get_option' )->alias( fn( $name, $default = false ) => $default );
		Functions\when( 'add_filter' )->alias( function ( $hook ) {
			$this->added_filters[] = $hook;

			return true;
		} );
	}

	protected function tearDown(): void {
		unset( $_SERVER['HTTP_REFERER'], $_GET['b2b'] );
		$_SERVER['REQUEST_URI'] = '/';
		Monkey\tearDown();
		parent::tearDown();
	}

	private function b2b_frontend(): void {
		$_SERVER['REQUEST_URI'] = '/panel-b2b/zamowienie/';
	}

	public function test_backorders_getter_is_not_filtered(): void {
		new Product();

		// Filtering the getter leaks into WC_Product::validate_props() and gets saved as stock status.
		$this->assertNotContains( 'woocommerce_product_get_backorders', $this->added_filters );
		$this->assertNotContains( 'woocommerce_product_variation_get_backorders', $this->added_filters );

		$this->assertContains( 'woocommerce_product_backorders_allowed', $this->added_filters );
		$this->assertContains( 'woocommerce_product_backorders_require_notification', $this->added_filters );
		$this->assertContains( 'woocommerce_product_get_stock_status', $this->added_filters );
		$this->assertContains( 'woocommerce_product_variation_get_stock_status', $this->added_filters );
		$this->assertContains( 'woocommerce_quantity_input_max', $this->added_filters );
	}

	public function test_b2b_frontend_allows_backorders(): void {
		$this->b2b_frontend();
		$product = new Product();

		$this->assertTrue( $product->filter_backorders_allowed( false ) );
		$this->assertFalse( $product->filter_backorders_require_notification( true ) );
		$this->assertSame( 'onbackorder', $product->filter_stock_status( 'outofstock' ) );
		$this->assertSame( 'instock', $product->filter_stock_status( 'instock' ) );
		$this->assertSame( -1, $product->filter_quantity_input_max( 5, null ) );
	}

	public function test_b2b_ajax_from_panel_allows_backorders(): void {
		$this->is_admin                = true;
		$this->doing_ajax              = true;
		$_SERVER['REQUEST_URI']        = '/?wc-ajax=checkout';
		$_SERVER['HTTP_REFERER']       = 'https://warfactory.pl/panel-b2b/zamowienie/';
		$product                       = new Product();

		$this->assertTrue( $product->filter_backorders_allowed( false ) );
		$this->assertSame( 'onbackorder', $product->filter_stock_status( 'outofstock' ) );
	}

	public static function non_b2b_contexts(): array {
		return [
			'b2c frontend'           => [ false, false, '/produkt/x/', null ],
			'b2c ajax'               => [ true, true, '/?wc-ajax=add_to_cart', 'https://warfactory.pl/produkt/x/' ],
			'admin screen on b2b url' => [ true, false, '/wp-admin/post.php?panel-b2b', null ],
		];
	}

	#[DataProvider( 'non_b2b_contexts' )]
	public function test_outside_b2b_context_values_pass_through( bool $is_admin, bool $ajax, string $uri, ?string $referer ): void {
		$this->is_admin         = $is_admin;
		$this->doing_ajax       = $ajax;
		$_SERVER['REQUEST_URI'] = $uri;
		if ( null !== $referer ) {
			$_SERVER['HTTP_REFERER'] = $referer;
		}
		$product = new Product();

		$this->assertFalse( $product->filter_backorders_allowed( false ) );
		$this->assertTrue( $product->filter_backorders_allowed( true ) );
		$this->assertTrue( $product->filter_backorders_require_notification( true ) );
		$this->assertSame( 'outofstock', $product->filter_stock_status( 'outofstock' ) );
		$this->assertSame( 5, $product->filter_quantity_input_max( 5, null ) );
	}
}
