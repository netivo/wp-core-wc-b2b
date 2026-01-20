<?php

namespace Netivo\Module\WooCommerce\B2B\Tests\Model;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Netivo\Module\WooCommerce\B2B\Model\Discount;
use PHPUnit\Framework\TestCase;

class DiscountTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_getters_and_setters() {
		$discount = new Discount();

		$discount->id      = 1;
		$discount->type    = 'percentage';
		$discount->type_id = 10;
		$discount->value   = '20';

		$this->assertEquals( 1, $discount->get_id() );
		$this->assertEquals( 'percentage', $discount->get_type() );
		$this->assertEquals( 10, $discount->get_type_id() );
		$this->assertEquals( '20', $discount->get_value() );
	}

	public function test_user_id_setter_fetches_user() {
		$user               = new \WP_User();
		$user->ID           = 5;
		$user->display_name = 'Test User';

		Functions\expect( 'get_user_by' )
			->with( 'id', 5 )
			->once()
			->andReturn( $user );

		$discount          = new Discount();
		$discount->user_id = 5;

		$this->assertEquals( 5, $discount->get_user_id() );
		$this->assertSame( $user, $discount->get_user() );
	}
}
