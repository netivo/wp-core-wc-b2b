<?php

namespace Netivo\Module\WooCommerce\B2B\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Netivo\Module\WooCommerce\B2B\Rewrite;
use PHPUnit\Framework\TestCase;

class RewriteTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_constructor_registers_actions_and_filters() {
		$rewrite = new Rewrite();

		$this->assertNotFalse( has_action( 'init', [ $rewrite, 'register_shop_endpoints' ] ) );
		$this->assertNotFalse( has_filter( 'query_vars', [ $rewrite, 'register_query_vars' ] ) );
	}

	public function test_register_query_vars() {
		$rewrite = new Rewrite();
		$vars    = [ 'foo', 'bar' ];
		$result  = $rewrite->register_query_vars( $vars );

		$this->assertContains( 'b2b', $result );
		$this->assertContains( 'foo', $result );
		$this->assertContains( 'bar', $result );
	}

	public function test_register_shop_endpoints() {
		Functions\expect( 'wc_get_permalink_structure' )
			->once()
			->andReturn( [ 'category_rewrite_slug' => 'product-category' ] );

		Functions\expect( 'get_option' )
			->with( 'nt_b2b_base_url' )
			->once()
			->andReturn( 'b2b' );

		Functions\expect( 'get_option' )
			->with( 'woocommerce_cart_page_id' )
			->once()
			->andReturn( 10 );

		Functions\expect( 'get_option' )
			->with( 'woocommerce_checkout_page_id' )
			->once()
			->andReturn( 11 );

		$cart_page            = new \WP_User(); // Using WP_User as generic object with post_name/ID
		$cart_page->post_name = 'cart';
		$cart_page->ID        = 10;

		$checkout_page            = new \WP_User();
		$checkout_page->post_name = 'checkout';
		$checkout_page->ID        = 11;

		Functions\expect( 'get_post' )
			->with( 10 )
			->once()
			->andReturn( $cart_page );

		Functions\expect( 'get_post' )
			->with( 11 )
			->once()
			->andReturn( $checkout_page );

		Monkey\Filters\expectApplied( 'add_rewrite_rule' )->never(); // add_rewrite_rule is a function, not a filter

		Functions\expect( 'add_rewrite_rule' )->times( 6 );

		$rewrite = new Rewrite();
		$rewrite->register_shop_endpoints();
		$this->assertTrue( true );
	}
}
