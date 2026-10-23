<?php
/**
 * Admin notification email for a new B2B account request.
 *
 * @package WooCommerce/Templates/Emails
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
        'currency'         => __( 'Waluta', 'netivo' ),
        'message'          => __( 'Uzasadnienie', 'netivo' )
];

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

    <p><?php echo $new_user
            ? esc_html__( 'Otrzymano nowe zgłoszenie o konto B2B od nowego użytkownika.', 'netivo' )
            : esc_html__( 'Otrzymano nowe zgłoszenie o konto B2B od istniejącego użytkownika.', 'netivo' ); ?></p>

    <h2 class="<?php echo esc_attr( $heading_class ); ?>"><?php _e( 'Dane zgłoszenia z formularza:', 'netivo' ); ?></h2>
    <ul>
        <?php foreach ( $form_data as $key => $value ) : ?>
            <?php if ( ! isset( $form_fields[ $key ] ) ) {
                continue;
            } ?>
            <li><strong><?php echo esc_html( $form_fields[ $key ] ); ?>:</strong> <?php echo esc_html( $value ); ?></li>
        <?php endforeach; ?>
    </ul>

    <p>
        <a href="<?php echo esc_url( $requests_url ); ?>"><?php _e( 'Przejdź do listy zgłoszeń B2B', 'netivo' ); ?></a>
    </p>

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
