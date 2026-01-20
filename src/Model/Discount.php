<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 * User: manveru
 * Date: 20.01.2026
 * Time: 14:02
 *
 */

namespace Netivo\Module\WooCommerce\B2B\Model;

use Netivo\AleSmaki\Model\Offer;
use Netivo\Core\Database\Entity;
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
	 * @Column(name=value,type=varchar(20),format=%s,required=true)
	 * @var string
	 */
	private string $value;

	private ?WP_User $user = null;

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
	public function get_value(): string {
		return $this->value;
	}

	/**
	 * @return WP_User|null
	 */
	public function get_user(): ?WP_User {
		return $this->user;
	}
}