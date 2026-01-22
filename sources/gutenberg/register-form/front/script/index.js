import Pristine from 'pristinejs';
import Form     from '@netivo/base-scripts/javascript/form';

let contact_form = document.querySelector( '.js-contact-form' );
if ( contact_form !== null ) {
  let loader = contact_form.querySelector( '[data-element="loader"]' );
  let recaptcha_key = contact_form.getAttribute( 'data-recaptcha' );
  let pristine = new Pristine( contact_form, {
    classTo: 'js-per', errorTextParent: 'js-per',
  } );

  new Form( contact_form, {
    action:          '/wp-json/netivo/v1/b2b-form', data: ( el ) => {
      return new Promise( ( resolve ) => {
        let data = {
          company_name: '',
          nip:          '',
          first_name:   '',
          last_name:    '',
          email:        '',
          phone:        '',
          message:      '',
          agree:        '',
          nonce:        '',
          recaptcha:    '',
        };

        let form_company_name = el.querySelector( '[name="company_name"]' ),
            form_nip          = el.querySelector( '[name="nip"]' ),
            form_first_name   = el.querySelector( '[name="first_name"]' ),
            form_last_name    = el.querySelector( '[name="last_name"]' ),
            form_email        = el.querySelector( '[name="email"]' ),
            form_phone        = el.querySelector( '[name="phone"]' ),
            form_message      = el.querySelector( '[name="message"]' ),
            form_agree        = el.querySelector( '[name="agree"]' ),
            form_nonce        = el.querySelector( '[name="nonce"]' );

        if ( form_company_name !== null ) {
          data.company_name = form_company_name.value;
        }
        if ( form_nip !== null ) {
          data.nip = form_nip.value;
        }
        if ( form_first_name !== null ) {
          data.first_name = form_first_name.value;
        }
        if ( form_last_name !== null ) {
          data.last_name = form_last_name.value;
        }
        if ( form_email !== null ) {
          data.email = form_email.value;
        }
        if ( form_phone !== null ) {
          data.phone = form_phone.value;
        }
        if ( form_message !== null ) {
          data.message = form_message.value;
        }
        if ( form_agree !== null ) {
          data.agree = form_agree.checked ? 1 : 0;
        }
        if ( form_nonce !== null ) {
          data.nonce = form_nonce.value;
        }

        if ( recaptcha_key !== '' ) {
          window.grecaptcha.ready( function() {
            window.grecaptcha
                  .execute( recaptcha_key, { action: 'contact' } )
                  .then( function( token ) {
                    data.recaptcha = token;
                    resolve( data );
                  } );
          } );
        }
        else {
          resolve( data );
        }
      } );
    }, beforeSubmit: ( el ) => {
      let is_valid = pristine.validate();
      if ( is_valid ) {
        loader.classList.add( 'loader--show-in-form' );
        let formResponse = contact_form.querySelector( '[data-element="response"]' );
        formResponse.style.display = 'flex';
      }
      else {
        let top = el.querySelector( '.has-danger' ).getBoundingClientRect().top + window.pageYOffset;
        //console.log(top);
        window.scrollTo( { top: top - 100, behavior: 'smooth' } );
      }
      return is_valid;
    }, success:      ( el, response ) => {
      let formResponse = contact_form.querySelector( '[data-element="response"]' );
      let formSuccess = contact_form.querySelector( '[data-response="success"]' );
      loader.classList.remove( 'loader--show-in-form' );
      formSuccess.innerText = response.message;
      formSuccess.style.display = 'block';
      setTimeout( () => {
        el.querySelector( '[name="company_name"]' ).value = '';
        el.querySelector( '[name="nip"]' ).value = '';
        el.querySelector( '[name="first_name"]' ).value = '';
        el.querySelector( '[name="last_name"]' ).value = '';
        el.querySelector( '[name="email"]' ).value = '';
        el.querySelector( '[name="phone"]' ).value = '';
        el.querySelector( '[name="message"]' ).value = '';
        el.querySelector( '#daneOsobowe' ).checked = false;
        formResponse.style.display = 'none';
      }, 6000 );
    }, error:        ( el, response ) => {
      //console.log(response);
      let formResponse = contact_form.querySelector( '[data-element="response"]' );
      let formError = contact_form.querySelector( '[data-response="error"]' );
      loader.classList.remove( 'loader--show-in-form' );
      formError.innerText = response;
      setTimeout( () => {
        formResponse.style.display = 'none';
      }, 6000 );
    },
  } );
}
