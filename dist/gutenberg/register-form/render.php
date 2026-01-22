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
            <label class="form__label" for="company_name"><?php esc_html_e( 'Nazwa firmy', 'netivo' ); ?></label>
            <input required data-pristine-required-message="<?php esc_attr_e( 'To pole jest wymagane', 'netivo' ); ?>" class="form__input"
                   type="text"
                   name="company_name" id="company_name" placeholder="<?php esc_attr_e( 'Nazwa firmy', 'netivo' ); ?>"/>
        </div>
        <div class="form__group js-per">
            <label class="form__label" for="nip"><?php esc_html_e( 'NIP', 'netivo' ); ?></label>
            <input required data-pristine-required-message="<?php esc_attr_e( 'To pole jest wymagane', 'netivo' ); ?>"
                   class="form__input" type="text" name="nip" id="nip"
                   placeholder="<?php esc_attr_e( 'NIP', 'netivo' ); ?>"/>
        </div>
    </div>
    <div class="form__row">
        <div class="form__group js-per">
            <label class="form__label" for="first_name"><?php esc_html_e( 'Imię', 'netivo' ); ?></label>
            <input required data-pristine-required-message="<?php esc_attr_e( 'To pole jest wymagane', 'netivo' ); ?>" class="form__input"
                   type="text"
                   name="first_name" id="first_name" placeholder="<?php esc_attr_e( 'Imię', 'netivo' ); ?>"/>
        </div>
        <div class="form__group js-per">
            <label class="form__label" for="last_name"><?php esc_html_e( 'Nazwisko', 'netivo' ); ?></label>
            <input required data-pristine-required-message="<?php esc_attr_e( 'To pole jest wymagane', 'netivo' ); ?>"
                   class="form__input" type="text" name="last_name" id="last_name"
                   placeholder="<?php esc_attr_e( 'Nazwisko', 'netivo' ); ?>"/>
        </div>
    </div>
    <div class="form__row">
        <div class="form__group js-per">
            <label class="form__label" for="email"><?php esc_html_e( 'Adres e-mail', 'netivo' ); ?></label>
            <input required data-pristine-required-message="<?php esc_attr_e( 'To pole jest wymagane', 'netivo' ); ?>"
                   data-pristine-email-message="<?php esc_attr_e( 'W tym polu musisz podać prawidłowy adres email', 'netivo' ); ?>"
                   class="form__input" type="email" name="email" id="email"
                   placeholder="<?php esc_attr_e( 'Adres e-mail', 'netivo' ); ?>"/>
        </div>
        <div class="form__group js-per">
            <label class="form__label" for="phone"><?php esc_html_e( 'Telefon', 'netivo' ); ?></label>
            <input required data-pristine-required-message="<?php esc_attr_e( 'To pole jest wymagane', 'netivo' ); ?>" class="form__input"
                   type="text"
                   name="phone" id="phone" placeholder="<?php esc_attr_e( 'Telefon', 'netivo' ); ?>"/>
        </div>
    </div>
    <div class="form__group form__group-100 js-per">
        <label class="form__label" for="message"><?php esc_html_e( 'Wiadomość', 'netivo' ); ?></label>
        <textarea class="form__input form__input--textarea" required
                  data-pristine-required-message="<?php esc_attr_e( 'To pole jest wymagane', 'netivo' ); ?>" rows="10" name="message" id="message"
                  placeholder="<?php esc_attr_e('Dlaczego checsz założyć konto B2B?', 'netivo'); ?>"></textarea>
    </div>
    <div class="form__group form__group-100 js-per">
        <div class="form__row-checkbox">
            <input required data-pristine-required-message="<?php esc_attr_e( 'To pole jest wymagane', 'netivo' ); ?>" id="daneOsobowe" type="checkbox"
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
