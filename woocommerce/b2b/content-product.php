<?php
/**
 * The template for displaying product content within loops
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;


global $product;

// Check if the product is a valid WooCommerce product and ensure its visibility before proceeding.
if ( ! is_a( $product, WC_Product::class ) ) {
	return;
}

$extra_class = "b2b-content-loop-product";

?>
<li <?php wc_product_class( esc_attr( $extra_class ), $product ); ?> data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">

	<?php
	global $product;

	$sku = $product ? $product->get_sku() : 'SKU';

	?>
	<span class="b2b-content-loop-product__sku"><span class="b2b-content-loop-product__sku-prefix"><?php echo __('kod: ', 'netivo');?></span><?php echo esc_html( $sku ); ?></span>

	<?php echo woocommerce_template_loop_product_title(); ?>


	<?php
	global $product;
	$stock_quantity = $product->get_stock_quantity() ?? 102;
	$class          = "b2b-content-loop-product__stock_quantity--green";

	if ( $stock_quantity < 100 ) {
		$class = "b2b-content-loop-product__stock_quantity--red";
	} else if ( $stock_quantity < 200 ) {
		$class = "b2b-content-loop-product__stock_quantity--yellow";
	}

	?>
	<div class="b2b-content-loop-product__stock_quantity <?php echo $class; ?>"><span class="b2b-content-loop-product__stock_quantity-prefix"><?php echo __( 'mag: ', 'netivo' ) ?></span><?php echo $stock_quantity; ?> <?php echo __( ' szt.', 'netivo' ) ?></div>

	<?php woocommerce_template_loop_price(); ?>

	<?php
	global $product;
	$stock_quantity = $product->get_stock_quantity();


	woocommerce_quantity_input( [
		'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
		'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
		'input_value' => $product->get_min_purchase_quantity(),
	] );
	?>


	<?php woocommerce_template_loop_add_to_cart(); ?>
	<?php // woocommerce_template_single_add_to_cart(); ?>
</li>