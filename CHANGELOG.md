# Changelog

## Version 1.0.1

- Fixed B2B orders saving `_stock_status = onbackorder` on products with backorders disabled, which made them look purchasable (active "add to cart" button, `BackOrder` availability) for b2c customers. B2B backorders are now enabled via the `woocommerce_product_backorders_allowed` and `woocommerce_product_backorders_require_notification` filters instead of filtering the `backorders` getter, which leaked into `WC_Product::validate_props()` on save.
