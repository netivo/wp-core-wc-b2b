<?php

namespace Netivo\Module\WooCommerce\B2B\Tests\Admin;

use Brain\Monkey;
use Netivo\Module\WooCommerce\B2B\Admin\Panel;
use PHPUnit\Framework\TestCase;

class PanelTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_constructor_initializes_permalink() {
		// Permalink constructor registers an action
		new Panel();
		$this->assertTrue( has_action( 'admin_init' ) );
	}
}
