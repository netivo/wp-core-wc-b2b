<?php
/**
 * Admin notification email for a new B2B account request (plain text).
 *
 * @package WooCommerce/Templates/Emails/Plain
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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

echo esc_html( wp_strip_all_tags( $email_heading ) ) . "\n\n";

echo $new_user
        ? esc_html__( 'Otrzymano nowe zgłoszenie o konto B2B od nowego użytkownika.', 'netivo' )
        : esc_html__( 'Otrzymano nowe zgłoszenie o konto B2B od istniejącego użytkownika.', 'netivo' );

echo "\n\n" . esc_html__( 'Dane zgłoszenia z formularza:', 'netivo' ) . "\n";
echo "----------------------------------------\n\n";

foreach ( $form_data as $key => $value ) {
    if ( ! isset( $form_fields[ $key ] ) ) {
        continue;
    }
    echo esc_html( $form_fields[ $key ] ) . ': ' . esc_html( $value ) . "\n";
}

echo "\n----------------------------------------\n\n";

echo esc_html__( 'Przejdź do listy zgłoszeń B2B:', 'netivo' ) . ' ' . esc_url( $requests_url ) . "\n";
