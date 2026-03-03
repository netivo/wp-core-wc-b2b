<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.6.0
 */
defined( 'ABSPATH' ) || exit;

global $nt_is_mobile;

get_header( 'shop' );

/**
 * Hook: woocommerce_before_main_content.
 *
 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
 * @hooked woocommerce_breadcrumb - 20
 * @hooked WC_Structured_Data::generate_website_data() - 30
 */
do_action( 'woocommerce_before_main_content' );

/**
 * Hook: woocommerce_shop_loop_header.
 *
 * @since 8.6.0
 *
 * @hooked woocommerce_product_taxonomy_archive_header - 10
 */
do_action( 'woocommerce_shop_loop_header' );

$sidebar = true;

if ( $nt_is_mobile || ( is_tax( "product_brand" ) && ! woocommerce_product_loop() ) ) {
	$sidebar = false;
}

?>

    <div class="b2b-woocommerce-grid">

		<?php if ( $sidebar && is_active_sidebar( 'sidebar-b2b' ) ) : ?>
            <aside class="b2b-woocommerce-grid__sidebar">
				<?php dynamic_sidebar( 'sidebar-b2b' ); ?>
            </aside>
		<?php endif; ?>

        <div class="b2b-woocommerce-grid__content">

	        <?php wc_get_template_part( 'b2b', 'archive-search-bar' ); ?>

			<?php if ( woocommerce_product_loop() ) : ?>


				<?php
				wc_get_template_part( 'b2b', 'archive-product-nav' );

				woocommerce_product_loop_start();

				if ( wc_get_loop_prop( 'total' ) ) {
					while ( have_posts() ) {
						the_post();

						/**
						 * Hook: woocommerce_shop_loop.
						 */
						do_action( 'woocommerce_shop_loop' );

						global $post, $product;
						$product = wc_get_product( get_the_ID() );

						wc_get_template_part( 'b2b', 'content-product' );
					}
				}

				woocommerce_product_loop_end();

				?>
                <div class="c-woocommerce-products-footer">
					<?php
					/**
					 * Hook: woocommerce_after_shop_loop.
					 *
					 * @hooked woocommerce_pagination - 10
					 */
					do_action( 'woocommerce_after_shop_loop' );
					?>
                </div>
			<?php else : ?>
				<?php

				/**
				 * Hook: woocommerce_no_products_found.
				 *
				 * @hooked wc_no_products_found - 10
				 */
				do_action( 'woocommerce_no_products_found' );
				?>
			<?php endif; ?>
        </div>
    </div>

    <div class="js-product-popup product-popup">
        <div class="product-popup__wrapper">
            <div class="js-product-popup__content product-popup__content">

            </div>
            <button class="js-product-popup__close product-popup__close">
                <svg width="24"
                     height="24"
                     viewBox="-0.5 0 25 25"
                     fill="none"
                     xmlns="http://www.w3.org/2000/svg"
                >
                    <path d="M3 21.32L21 3.32001"
                          stroke="#000000"
                          stroke-width="1.5"
                          stroke-linecap="round"
                          stroke-linejoin="round"
                    />
                    <path d="M3 3.32001L21 21.32"
                          stroke="#000000"
                          stroke-width="1.5"
                          stroke-linecap="round"
                          stroke-linejoin="round"
                    />
                </svg>
            </button>
        </div>
    </div>
<?php
/**
 * Hook: woocommerce_after_main_content.
 *
 * @hooked \Netivo\Elazienki\Theme\WooCommerce\Archive::full_description() - 5
 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action( 'woocommerce_after_main_content' );

get_footer( 'shop' );
