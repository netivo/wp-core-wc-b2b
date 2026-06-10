<?php if ( ! is_checkout() && ! is_cart() ) : ?>
    <div class="b2b-footer">
        <div class="b2b-go-to-cart-wrapper">

			<?php if ( ! wp_is_mobile() ) : ?>
				<?php get_template_part( 'parts/header/mobile/cart' ); ?>
			<?php endif; ?>

            <a href="<?php echo wc_get_checkout_url(); ?>"
               class="button wc-forward js-b2b-checkout-btn<?php echo WC()->cart->is_empty() ? ' button--disabled' : ''; ?>"
            ><?php echo apply_filters( 'wpml_translate_single_string', 'Przejdź do zamówienia', 'netivo', 'Przejdź do zamówienia' ); ?></a>

        </div>
    </div>
<?php endif; ?>