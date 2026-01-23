<?php

namespace Netivo\Module\WooCommerce\B2B\Tests\Admin\Settings;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Netivo\Module\WooCommerce\B2B\Admin\Settings\Permalink;
use PHPUnit\Framework\TestCase;

class PermalinkTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_constructor_registers_admin_init() {
		$permalink = new Permalink();
		$this->assertNotFalse( has_action( 'admin_init', [ $permalink, 'register_settings' ] ) );
	}

	public function test_register_settings() {
		Functions\expect( 'add_settings_field' )
			->atLeast()->once();

		Functions\expect( 'register_setting' )
			->atLeast()->once();

		Functions\expect( '__' )
			->andReturnFirstArg();

		Functions\expect( 'wp_verify_nonce' )
			->andReturn( false );

		$permalink = new Permalink();
		$permalink->register_settings();

		$this->assertTrue( true ); // Mark as not risky
	}

	public function test_register_settings_saves_and_flushes_on_post() {
		$_POST['nt_b2b_base_url'] = 'new-b2b';
		$_POST['_wpnonce']        = 'valid-nonce';

		Functions\expect( 'add_settings_field' )->atLeast()->once();
		Functions\expect( 'register_setting' )->atLeast()->once();
		Functions\expect( '__' )->andReturnFirstArg();

		Functions\expect( 'wp_verify_nonce' )
			->with( 'valid-nonce', 'update-permalink' )
			->once()
			->andReturn( true );

		Functions\expect( 'sanitize_title' )
			->with( 'new-b2b' )
			->once()
			->andReturn( 'new-b2b' );

		Functions\expect( 'update_option' )
			->with( 'nt_b2b_base_url', 'new-b2b' )
			->once();

		Functions\expect( 'flush_rewrite_rules' )
			->once();

		$permalink = new Permalink();
		$permalink->register_settings();

		$this->assertTrue( true );
		unset( $_POST['nt_b2b_base_url'] );
		unset( $_POST['_wpnonce'] );
	}

	public function test_render_slug_field() {
		Functions\expect( 'get_option' )
			->with( 'nt_b2b_base_url', 'panel-b2b' )
			->once()
			->andReturn( 'custom-b2b' );

		Functions\expect( 'esc_attr' )
			->andReturnFirstArg();

		$permalink = new Permalink();

		$this->expectOutputRegex( '/name="nt_b2b_base_url"/' );
		$this->expectOutputRegex( '/value="custom-b2b"/' );
		$permalink->render_slug_field();
	}
}
