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
use Netivo\Module\WooCommerce\B2B\Controller\User as UserController;
use Netivo\Module\WooCommerce\B2B\Model\Discount as DiscountModel;

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
	 * Initializes the constructor for the class.
	 *
	 * This method sets up the necessary components, including the UserController,
	 * Rewrite object, and database table creation for the DiscountModel. Additionally,
	 * it initializes the admin Panel if the current environment is in the admin context.
	 *
	 * @return void
	 */
	protected function __construct() {
		$this->userController = new UserController();
		new Rewrite();
		EntityManager::createTable( DiscountModel::class );

		if ( is_admin() ) {
			new Panel();
		}
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
}