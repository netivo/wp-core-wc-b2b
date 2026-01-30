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

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );
$heading_class              = $email_improvements_enabled ? 'email-order-detail-heading' : '';

$form_fields = [
        'personal_name'    => __( 'Imię', 'netivo' ),
        'personal_surname' => __( 'Nazwisko', 'netivo' ),
        'company_name'     => __( 'Nazwa firmy', 'netivo' ),
        'email_address'    => __( 'Adres email', 'netivo' ),
        'phone_number'     => __( 'Numer telefonu', 'netivo' ),
        'company_nip'      => __( 'NIP', 'netivo' ),
        'message'          => __( 'Uzasadnienie', 'netivo' )
];

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>


    <p><?php _e( 'Twoja prośba o konto B2B została przyjęta i oczekuje na zatwierdzenie. ', 'netivo' ); ?></p><?php // phpcs:ignore WordPress.XSS.EscapeOutput ?>
<?php if ( $new_user ) : ?>
    <p><?php _e( 'Dostałeś już, bądź za chwilę dostaniesz wiadomość email z danymi logowania. Twoje konto w systemie zostało już założone i możesz z niego korzystać jak z normalnego konta. W momencie akceptacji dostaniesz powiadomienie, a twoje konto dostanie nowe uprawnienia.', 'netivo' ); ?></p>
<?php else: ?>
    <p><?php _e( 'Dalej możesz korzystać ze swojego konta jak do tej pory. Po zatwierdzeniu dostaniesz powiadomienie, a twoje konto dostanie nowe uprawnienia.', 'netivo' ); ?></p>
<?php endif; ?>

    <h2 class="<?php echo esc_attr( $heading_class ); ?>"><?php _e( 'Dane zgłoszenia z formularza:', 'netivo' ); ?></h2>
    <ul>
        <?php foreach ( $form_data as $key => $value ) : ?>
            <li><strong><?php echo esc_html( $form_fields[ $key ] ); ?>:</strong> <?php echo esc_html( $value ); ?></li>
        <?php endforeach; ?>
    </ul>

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
