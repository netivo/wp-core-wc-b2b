<?php
/**
 * Created by Netivo for Netivo Woocommerce
 * Creator: Netivo
 * Creation date: Thu, 17 Jul 2025 14:21:42 GMT
 */

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 404 Forbidden' );
	exit;
}
$consent_text = $attributes['consent_text'] ?? '';


?>

<form class="form form--contact js-contact-form contact-block__col" method="post" novalidate
      data-recaptcha="<?php echo esc_attr( get_option( 'nt_captcha_key' ) ); ?>">
    <div class="form__response" data-element="response">
        <div class="loader" data-element="loader">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
        </div>
        <div class="form__response-message" data-response="success"></div>
        <div class="form__response-message" data-response="error"></div>
    </div>
    <div class="form__row">
        <div class="form__group js-per">
            <label class="form__label" for="firstname"><?php esc_html_e( 'Imię', 'netivo' ); ?></label>
            <input required data-pristine-required-message="To pole jest wymagane" class="form__input"
                   type="text"
                   name="first_name" id="firstname" placeholder="<?php esc_attr_e( 'Imię', 'netivo' ); ?>"/>
        </div>
        <div class="form__group js-per">
            <label class="form__label" for="email"><?php esc_html_e( 'Adres e-mail', 'netivo' ); ?></label>
            <input required data-pristine-required-message="To pole jest wymagane"
                   data-pristine-email-message="W tym polu musisz podać prawidłowy adres email"
                   class="form__input" type="email" name="email" id="email"
                   placeholder="<?php esc_attr_e( 'Adres e-mail', 'netivo' ); ?>"/>
        </div>
    </div>
    <div class="form__row">
        <div class="form__group form__group-100 js-per">
            <label class="form__label"
                   for="message-title"><?php esc_html_e( 'Zapytanie do sklepu', 'netivo' ); ?></label>
            <input class="form__input" type="text" name="message_title" id="message-title" required
                   data-pristine-required-message="To pole jest wymagane"
                   placeholder="<?php esc_attr_e( 'Zapytanie do sklepu', 'netivo' ); ?>"/>
        </div>
    </div>
    <div class="form__group form__group-100 js-per">
        <label class="form__label" for="message"><?php esc_html_e( 'Treść wiadomości', 'netivo' ); ?></label>
        <textarea class="form__input form__input--textarea" required
                  data-pristine-required-message="To pole jest wymagane" rows="10" name="message" id="message"
                  placeholder="Treść wiadomości"></textarea>
    </div>
    <div class="form__group form__group-100 js-per">
        <div class="form__row-checkbox">
            <input required data-pristine-required-message="To pole jest wymagane" id="daneOsobowe" type="checkbox"
                   name="agree" class="form__input-checkbox"/>
            <label for="daneOsobowe" class="form__label-checkbox">
				<?php echo wp_kses_post( sprintf( $consent_text, ( esc_url( get_privacy_policy_url() ) ) ) ); ?>
            </label>
        </div>
    </div>
    <div class="form__group form__group-100 form__group-submit">
        <input type="hidden" name="recaptcha" id="recaptcha">
        <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'contact' ) ); ?>"/>
        <button type="submit" class="form__button"
                name="send"><?php esc_html_e( 'Wyślij wiadomość', 'netivo' ); ?></button>
    </div>
</form>
