<?php
/**
 * Customer on-hold order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-on-hold-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates/Emails
 * @version 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

    <p><?php _e( 'Dzień dobry', 'netivo'); ?></p>

    <p><?php _e( 'dziękuję za zainteresowanie ofertą hurtową Warfactory. Nazywam się Marek Laskowski i w naszej firmie odpowiadam za opiekę nad klientami sklepowymi.', 'netivo'); ?>

    <p><?php _e( 'Pod tym adresem: <a href="https://warfactory.pl/oferta-sklepowa/">warfactory.pl/oferta-sklepowa</a> znajdziesz wszystkie istotne informacje logistyczne oraz zestawienie naszych kluczowych marek. ', 'netivo' ); ?></p><?php // phpcs:ignore WordPress.XSS.EscapeOutput ?>

    <p><?php _e( 'Twoje konto B2B zostało już aktywowane i możesz z niego korzystać', 'netivo' ); ?></p>

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
