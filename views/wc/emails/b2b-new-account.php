<?php
/**
 * Email: Nowe konto B2B — powitanie z linkiem do ustawienia hasła.
 *
 * Zmienne dostępne w szablonie:
 *   $email_heading    (string)
 *   $user             (WP_User)
 *   $set_password_url (string)
 *   $email            (WC_Email)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php printf(
	/* translators: %s: first name */
	esc_html__( 'Cześć %s,', 'netivo' ),
	esc_html( $user->first_name ?: $user->display_name )
); ?></p>

<p><?php esc_html_e( 'Twoje konto w sklepie B2B warfactory.pl zostało utworzone. Zanim zaczniesz korzystać z konta, musisz ustawić swoje hasło.', 'netivo' ); ?></p>

<p><?php esc_html_e('Twoje konto jest teraz zwykłym kontem B2C, z którego możesz składać zamówienia standardowe. Twoja prośba o dołączenie do programu B2B czeka na zatwierdzenie.', 'netivo'); ?></p>

<p><?php esc_html_e( 'Kliknij przycisk poniżej, aby przejść do strony ustawienia hasła:', 'netivo' ); ?></p>

<p style="text-align:center;">
	<a href="<?php echo esc_url( $set_password_url ); ?>"
	   style="display:inline-block;background-color:#333;color:#fff;padding:12px 28px;text-decoration:none;border-radius:3px;font-weight:bold;">
		<?php esc_html_e( 'Ustaw hasło', 'netivo' ); ?>
	</a>
</p>

<p><?php esc_html_e( 'Jeśli przycisk nie działa, skopiuj poniższy link i wklej go w przeglądarce:', 'netivo' ); ?></p>

<p><a href="<?php echo esc_url( $set_password_url ); ?>"><?php echo esc_url( $set_password_url ); ?></a></p>

<p><?php esc_html_e( 'Twoje zgłoszenie rejestracyjne zostało przyjęte i oczekuje na weryfikację przez nasz zespół. Powiadomimy Cię e-mailem, gdy konto zostanie aktywowane.', 'netivo' ); ?></p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
