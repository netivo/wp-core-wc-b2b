<?php

namespace {
	require_once __DIR__ . '/../vendor/autoload.php';

	Brain\Monkey\setUp();
	if ( ! class_exists( 'WP_User' ) ) {
		class WP_User {
			public $ID;
			public $display_name;
			public $post_name;
		}
	}

	if ( ! class_exists( 'WP_Error' ) ) {
		class WP_Error {
			protected $code;
			protected $message;

			public function __construct( $code = '', $message = '' ) {
				$this->code    = $code;
				$this->message = $message;
			}

			public function get_error_code() {
				return $this->code;
			}
		}
	}

	if ( ! class_exists( 'WP_REST_Request' ) ) {
		class WP_REST_Request {
			public function get_param( $key ) {
			}
		}
	}
}

namespace Netivo\Core\Database {
	if ( ! class_exists( 'Netivo\Core\Database\EntityManager' ) ) {
		class EntityManager {
			public static function createTable( $class ) {
			}
		}
	}

	if ( ! class_exists( 'Netivo\Core\Database\Entity' ) ) {
		class Entity {
			protected string $_state = 'new';

			public function get_state() {
				return $this->_state;
			}

			public function set_state( $state ) {
				$this->_state = $state;
			}
		}
	}
}

namespace Netivo\Core {
	if ( ! class_exists( 'Netivo\Core\RestController' ) ) {
		class RestController {
			public function build_route( $callback, $method ) {
			}
		}
	}
}

namespace {
	register_shutdown_function( function () {
		Brain\Monkey\tearDown();
	} );
}
