<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 * User: manveru
 * Date: 20.01.2026
 * Time: 14:02
 *
 */

namespace Netivo\Module\WooCommerce\B2B\Model;

use Netivo\Core\Database\Entity;
use Netivo\Core\Database\EntityManager;
use WC_Product;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

/**
 * Discount entity class
 *
 * @Table(name="nt_b2b_discounts",version=1.0)
 */
class Discount extends Entity {

	/**
	 * @Column(name=id,type=bigint(20),format=%d,primary=true,required=true)
	 * @var int
	 */
	private int $id;

	/**
	 * @Column(name=user_id,type=bigint(20),format=%s,primary=false,required=true)
	 * @var int
	 */
	private int $user_id;

	/**
	 * @Column(name=type,type=varchar(20),format=%s,required=true)
	 * @var string
	 */
	private string $type;

	/**
	 * @Column(name=type_id,type=bigint(20),format=%s,primary=false,required=true)
	 * @var int
	 */
	private int $type_id;

	/**
	 * @Column(name=price_type,type=varchar(20),format=%s,required=true)
	 * @var string
	 */
	private string $price_type;

	/**
	 * @Column(name=value,type=varchar(20),format=%s,required=true)
	 * @var string
	 */
	private string $value;

	private ?WP_User $user = null;

	/**
	 * Dynamically sets the value of a class property if it exists.
	 * Additionally, performs specific actions based on the property name and state.
	 *
	 * @param string $name The name of the property to set.
	 * @param mixed $value The value to assign to the property.
	 *
	 * @return void
	 */
	public function __set( string $name, mixed $value ) {
		if ( property_exists( $this, $name ) ) {
			$this->$name = $value;
			if ( $name === 'user_id' ) {
				$user = get_user_by( 'id', $value );
				if ( ! empty( $user ) ) {
					$this->user = $user;
				}
			}
			if ( $this->get_state() === 'existing' ) {
				$this->set_state( 'changed' );
			}
		}
	}

	/**
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function get_user_id(): int {
		return $this->user_id;
	}

	/**
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * @return int
	 */
	public function get_type_id(): int {
		return $this->type_id;
	}

	/**
	 * @return string
	 */
	public function get_price_type(): string {
		return $this->price_type;
	}

	/**
	 * @return string
	 */
	public function get_value(): string {
		return $this->value;
	}

	/**
	 * @return WP_User|null
	 */
	public function get_user(): ?WP_User {
		return $this->user;
	}

	/**
	 * Retrieves the product discounts associated with a specific user and product.
	 *
	 * @param int|string $user_id The ID of the user for whom discounts are being retrieved.
	 * @param WC_Product $product The ID of the product for which discounts are being retrieved.
	 *
	 * @return Discount|null An array of discounts if found, or null if no discounts are available.
	 */
	public static function get_product_discount_for_user( int|string $user_id, WC_Product $product ): ?Discount {
		$em = EntityManager::get( self::class );

		try {
			$product_discounts = $em->findAll( [
				'user_id' => [ 'type' => '%s', 'value' => $user_id ],
				'type'    => [ 'type' => '%s', 'value' => 'product' ],
				'type_id' => [ 'type' => '%s', 'value' => $product->get_id() ]
			] );
			if ( ! empty( $product_discounts ) ) {
				return $product_discounts[0];
			}
			$product_categories   = $product->get_category_ids();
			$categories_discounts = $em->findAll( [
				'user_id' => [ 'type' => '%s', 'value' => $user_id ],
				'type'    => [ 'type' => '%s', 'value' => 'category' ],
				'type_id' => [ 'operator' => 'IN', 'type' => '(%s)', 'value' => implode( ',', $product_categories ) ]
			] );
			if ( ! empty( $categories_discounts ) ) {
				if ( count( $categories_discounts ) === 1 ) {
					return $categories_discounts[0];
				}
				$max          = 0;
				$max_discount = null;
				foreach ( $categories_discounts as $dsc ) {
					if ( $dsc->get_value() > $max ) {
						$max          = $dsc->get_value();
						$max_discount = $dsc;
					}
				}

				return $max_discount;
			}
		} catch ( \ReflectionException $e ) {
			return null;
		}

		return null;
	}

}