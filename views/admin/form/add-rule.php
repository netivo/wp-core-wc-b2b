<?php
/**
 * Created by Netivo for wp-core-wc-b2b
 * User: manveru
 * Date: 30.01.2026
 * Time: 15:25
 *
 * @var $categories WP_Term_Query
 * @var $form_action string
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

?>
<div class="form-wrap">
    <form method="post" action="<?php echo esc_url( $form_action ); ?>" class="validate js-add-rule-form">
        <div class="form-field form-required">
            <label for="type"><?php echo esc_html__( 'Rodzaj elementu', 'netivo' ) ?></label>
            <select name="type" id="type" data-element="type-select" required aria-required="true">
                <option value="product"><?php echo esc_html__( 'Produkt', 'netivo' ) ?></option>
                <option value="category"><?php echo esc_html__( 'Kategoria', 'netivo' ) ?></option>
            </select>
        </div>
        <div class="form-field" data-element="category-select">
            <label for="category"><?php echo esc_html__( 'Kategoria', 'netivo' ); ?></label>
            <select
                    id="category"
                    class="wc-category-search"
                    name="category"
                    data-placeholder="<?php echo esc_attr__( 'Szukaj kategorii ...', 'netivo' ); ?>"
                    data-action="woocommerce_json_search_product_categories"
            >
				<?php foreach ( $categories->get_terms() as $category ) { ?>
                    <option value="<?php echo esc_attr( $category->term_id ); ?>"><?php echo esc_attr( $category->name );
						?></option>';
				<?php } ?>
            </select>
        </div>
        <div class="form-field" data-element="product-select">
            <label for="product"><?php echo esc_html__( 'Produkt', 'netivo' ); ?></label>
            <select
                    id="product"
                    class="wc-product-search"
                    name="product"
                    data-placeholder="<?php echo esc_attr__( 'Szukaj produktu ...', 'netivo' ); ?>"
                    data-action="woocommerce_json_search_products_and_variations"
                    style="width: 50%;"
            >
            </select>
        </div>
        <div class="form-field form-required">
            <label for="type"><?php echo esc_html__( 'Typ rabatu', 'netivo' ) ?></label>
            <select name="type" id="type" data-element="type-select" required aria-required="true">
                <option value="percent"><?php echo esc_html__( 'Procentowy', 'netivo' ) ?></option>
                <option value="price"><?php echo esc_html__( 'Kwotowy', 'netivo' ) ?></option>
            </select>
        </div>
        <div class="form-field form-required">
            <label for="value"><?php echo esc_html__( 'Wartość rabatu', 'netivo' ); ?></label>
            <input name="value" id="value" type="number" value="" aria-required="true" required
                   aria-describedby="value-description"/>
            <p id="value-description"><?php echo esc_html__( 'Podaj wartość rabatu bez podawania jednostki', 'netivo' ); ?></p>
        </div>
        <p class="submit">
			<?php submit_button( __( 'Dodaj regułę', 'netivo' ), 'primary', 'add-b2b-rule', false ); ?>
        </p>
    </form>
</div>
<script>
  let form = document.querySelector( '.js-add-rule-form' );
  if ( form !== null ) {
    let typeSelect = form.querySelector( '[data-element="type-select"]' );
    let categorySelect = form.querySelector( '[data-element="category-select"]' );
    let productSelect = form.querySelector( '[data-element="product-select"]' );

    let showType = () => {
      let type = typeSelect.value;
      categorySelect.style.display = 'none';
      productSelect.style.display = 'none';
      if ( type === 'category' ) {
        categorySelect.style.display = 'block';
      }
      else if ( type === 'product' ) {
        productSelect.style.display = 'block';
      }
    };

    showType();
    typeSelect.addEventListener( 'change', ( event ) => {
      showType();
    } );
  }
</script>