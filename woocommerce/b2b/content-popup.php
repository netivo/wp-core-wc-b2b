<?php
global $product;
?>

<?php echo wp_get_attachment_image( get_post_thumbnail_id( $product->get_id() ), 'full' ); ?>

<div class="product-popup__content-data">

	<?php
	woocommerce_template_single_title();
	woocommerce_template_single_rating();
	woocommerce_template_single_price();
	woocommerce_template_single_excerpt();
	woocommerce_template_single_meta();
	woocommerce_template_single_sharing();
	?>
</div>
