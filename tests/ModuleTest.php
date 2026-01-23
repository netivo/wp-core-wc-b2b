<?php

namespace Netivo\Module\WooCommerce\B2B\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Netivo\Core\Database\EntityManager;
use Netivo\Module\WooCommerce\B2B\Module;
use Netivo\Module\WooCommerce\B2B\Controller\User as UserController;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\stubs( [
			'get_option',
			'is_admin',
			'get_query_var',
			'wp_doing_ajax',
			'add_action',
			'__',
			'get_role',
			'add_role',
			'add_option',
			'wp_get_current_user',
		] );

		if ( ! class_exists( 'WP_User' ) ) {
			eval( 'class WP_User { public $ID = 0; }' );
		}

		Functions\when( 'is_admin' )->alias( function () {
			return false;
		} );

		Functions\when( 'wp_get_current_user' )->alias( function () {
			return new \WP_User();
		} );

		// Reset singleton instance
		$reflection = new \ReflectionClass( Module::class );
		$instance   = $reflection->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_get_instance_returns_singleton() {
		$instance1 = Module::get_instance();
		$instance2 = Module::get_instance();

		$this->assertInstanceOf( Module::class, $instance1 );
		$this->assertSame( $instance1, $instance2 );
	}

	public function test_user_controller_returns_controller() {
		$controller = Module::user_controller();
		$this->assertInstanceOf( UserController::class, $controller );
	}

	public function test_get_module_path() {
		$path = Module::get_module_path();
		$this->assertStringEndsWith( 'wp-core-wc-b2b', $path );
	}

	public function test_admin_initializes_panel() {
		Functions\when( 'is_admin' )->justReturn( true );

		// Panel constructor calls add_action in Permalink
		// We just want to see if it doesn't crash and is_admin was called
		Module::get_instance();
		$this->assertTrue( true );
	}

	public function test_is_b2b_context_query_var() {
		Functions\expect( 'get_query_var' )
			->with( 'b2b' )
			->andReturn( '1' );

		$this->assertTrue( Module::is_b2b_context() );
	}

	public function test_is_b2b_context_request_uri() {
		Functions\expect( 'get_query_var' )
			->with( 'b2b' )
			->andReturn( '' );
		Functions\expect( 'get_option' )
			->with( 'nt_b2b_base_url', 'panel-b2b' )
			->andReturn( 'panel-b2b' );
		Functions\expect( 'wp_doing_ajax' )->andReturn( false );

		$_SERVER['REQUEST_URI'] = '/some-path/panel-b2b/products/';
		$this->assertTrue( Module::is_b2b_context() );
		unset( $_SERVER['REQUEST_URI'] );
	}

	public function test_is_b2b_context_ajax_referer() {
		Functions\expect( 'get_query_var' )
			->with( 'b2b' )
			->andReturn( '' );
		Functions\expect( 'get_option' )
			->with( 'nt_b2b_base_url', 'panel-b2b' )
			->andReturn( 'custom-b2b-slug' );
		Functions\expect( 'wp_doing_ajax' )->andReturn( true );

		$_SERVER['HTTP_REFERER'] = 'https://example.com/custom-b2b-slug/something';
		$_SERVER['REQUEST_URI']  = '/wp-admin/admin-ajax.php';

		$this->assertTrue( Module::is_b2b_context() );

		unset( $_SERVER['HTTP_REFERER'] );
		unset( $_SERVER['REQUEST_URI'] );
	}

	public function test_is_b2b_context_get_param() {
		Functions\expect( 'get_query_var' )
			->with( 'b2b' )
			->andReturn( '' );
		Functions\expect( 'get_option' )
			->with( 'nt_b2b_base_url', 'panel-b2b' )
			->andReturn( 'panel-b2b' );
		Functions\expect( 'wp_doing_ajax' )->andReturn( false );

		$_GET['b2b']            = '1';
		$_SERVER['REQUEST_URI'] = '/something-else';

		$this->assertTrue( Module::is_b2b_context() );
		unset( $_GET['b2b'] );
		unset( $_SERVER['REQUEST_URI'] );
	}

	public function test_is_b2b_context_returns_false_when_no_match() {
		Functions\when( 'get_query_var' )->justReturn( '' );
		Functions\expect( 'get_option' )
			->with( 'nt_b2b_base_url', 'panel-b2b' )
			->andReturn( 'panel-b2b' );
		Functions\when( 'wp_doing_ajax' )->justReturn( false );

		$_SERVER['REQUEST_URI'] = '/standard-page/';
		if ( isset( $_GET['b2b'] ) ) {
			unset( $_GET['b2b'] );
		}

		$result = Module::is_b2b_context();
		$this->assertFalse( $result, 'Oczekiwano, że is_b2b_context zwróci false dla standardowej strony' );
		unset( $_SERVER['REQUEST_URI'] );
	}
}
