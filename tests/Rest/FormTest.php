<?php

namespace Netivo\Module\WooCommerce\B2B\Tests\Rest;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Netivo\Module\WooCommerce\B2B\Rest\Form;
use Netivo\Module\WooCommerce\B2B\Module;
use Netivo\Module\WooCommerce\B2B\Controller\User as UserController;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_register_routes() {
		// RestController has build_route method
		$form = \Mockery::mock( Form::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$form->shouldReceive( 'build_route' )->once()->with( [ $form, 'form_register' ], 'POST' );

		$form->register_routes();
		$this->assertTrue( true );
	}

	public function test_form_register_missing_fields() {
		$form    = new Form();
		$request = \Mockery::mock( \WP_REST_Request::class );
		$request->shouldReceive( 'get_param' )->andReturn( '' );

		Functions\expect( '__' )->andReturnFirstArg();

		$result = $form->form_register( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 500, $result->get_error_code() );
	}

	public function test_form_register_success_new_user() {
		$form    = new Form();
		$request = \Mockery::mock( \WP_REST_Request::class );

		$params = [
			'personal_name'    => 'Jan',
			'personal_surname' => 'Kowalski',
			'company_name'     => 'Firma',
			'company_nip'      => '1234567890',
			'email_address'    => 'jan@example.com',
			'phone_number'     => '123456789',
			'message'          => 'Hej',
			'captcha'          => 'valid-captcha'
		];

		$request->shouldReceive( 'get_param' )->andReturnUsing( function ( $key ) use ( $params ) {
			return $params[ $key ] ?? null;
		} );

		// Mock validate_captcha
		$form_mock = \Mockery::mock( Form::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$form_mock->shouldReceive( 'validate_captcha' )->once()->andReturn( true );

		// Mock Module and UserController
		Functions\expect( 'is_admin' )->andReturn( false );

		$user_controller = \Mockery::mock( UserController::class );
		$user_controller->shouldReceive( 'user_exists' )->with( 'jan@example.com' )->once()->andReturn( false );
		$user_controller->shouldReceive( 'register_user' )->once();

		// Manually set the controller in the Module singleton
		$module     = Module::get_instance();
		$reflection = new \ReflectionClass( $module );
		$property   = $reflection->getProperty( 'userController' );
		$property->setAccessible( true );
		$property->setValue( $module, $user_controller );

		Functions\expect( '__' )->andReturnFirstArg();

		$result = $form_mock->form_register( $request );

		$this->assertIsArray( $result );
		$this->assertEquals( 'success', $result['status'] );
	}
}
