<div class="b2b-shop-search">

    <form role="search"
          method="get"
          action="<?php echo get_home_url(); ?>/panel-b2b/"
          class="wp-block-search__no-button wp-block-search"
    >
        <label class="wp-block-search__label screen-reader-text"
               for="wp-block-search__input-1"
        ><?php echo apply_filters( 'wpml_translate_single_string',
				'Search', 'netivo', 'Search' ); ?></label>
        <div class="wp-block-search__inside-wrapper ">
            <input class="wp-block-search__input"
                   id="wp-block-search__input-1"
                   placeholder="<?php echo __( 'Szybkie filtrowanie (nazwa, EAN, kod produktu)', 'netivo' ) ?>"
                   value="<?php echo get_search_query(); ?>"
                   type="search"
                   name="s"
            >
            <input type="hidden"
                   name="post_type"
                   value="product"
            >
            <input type="hidden"
                   name="b2b"
                   value="1"
            >
            <button type="button"
                    class="js-wp-block-search__close wp-block-search__close"
            >
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
    </form>


	<?php woocommerce_catalog_ordering(); ?>
</div>