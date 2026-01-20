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
		Functions\expect( 'is_admin' )->andReturn( false );
		// Functions\stubs doesn't work for static methods this way

		$instance1 = Module::get_instance();
		$instance2 = Module::get_instance();

		$this->assertInstanceOf( Module::class, $instance1 );
		$this->assertSame( $instance1, $instance2 );
	}

	public function test_user_controller_returns_controller() {
		Functions\expect( 'is_admin' )->andReturn( false );

		$controller = Module::user_controller();
		$this->assertInstanceOf( UserController::class, $controller );
	}

	public function test_get_module_path() {
		$path = Module::get_module_path();
		$this->assertStringEndsWith( 'wp-core-wc-b2b', $path );
	}

	public function test_admin_initializes_panel() {
		Functions\expect( 'is_admin' )->once()->andReturn( true );

		// Panel constructor calls add_action in Permalink
		// We just want to see if it doesn't crash and is_admin was called
		Module::get_instance();
		$this->assertTrue( true );
	}
}
