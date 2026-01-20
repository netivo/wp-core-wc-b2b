import Pristine from "pristinejs";
import Form from "@netivo/base-scripts/javascript/form";

let contact_form = document.querySelector( ".js-contact-form" );
if ( contact_form !== null ) {
  let loader = contact_form.querySelector( '[data-element="loader"]' );
  let recaptcha_key = contact_form.getAttribute( "data-recaptcha" );
  let pristine = new Pristine( contact_form, {
    classTo:         "js-per",
    errorTextParent: "js-per"
  } );

  new Form( contact_form, {
    action:       "/wp-json/netivo/v1/b2b-form",
    data:         ( el ) => {
      return new Promise( ( resolve ) => {
        let data = {
          first_name:    "",
          email:         "",
          message_title: "",
          message:       "",
          nonce:         "",
          recaptcha:     ""
        };

        let form_first_name = el.querySelector( '[name="first_name"]' ),
          form_email = el.querySelector( '[name="email"]' ),
          form_message_title = el.querySelector( '[name="message_title"]' ),
          form_message = el.querySelector( '[name="message"]' ),
          form_nonce = el.querySelector( '[name="nonce"]' );

        if ( form_first_name !== null ) {
          data.first_name = form_first_name.value;
        }
        if ( form_email !== null ) {
          data.email = form_email.value;
        }
        if ( form_message_title !== null ) {
          data.message_title = form_message_title.value;
        }
        if ( form_message !== null ) {
          data.message = form_message.value;
        }
        if ( form_nonce !== null ) {
          data.nonce = form_nonce.value;
        }

        window.grecaptcha.ready( function () {
          window.grecaptcha
                .execute( recaptcha_key, { action: "contact" } )
                .then( function ( token ) {
                  data.recaptcha = token;
                  resolve( data );
                } );
        } );
      } );
    },
    beforeSubmit: ( el ) => {
      let is_valid = pristine.validate();
      if ( is_valid ) {
        loader.classList.add( "loader--show-in-form" );
        let formResponse = contact_form.querySelector(
          '[data-element="response"]'
        );
        formResponse.style.display = "flex";
      }
      else {
        let top =
          el.querySelector( ".has-danger" ).getBoundingClientRect().top +
          window.pageYOffset;
        //console.log(top);
        window.scrollTo( { top: top - 100, behavior: "smooth" } );
      }
      return is_valid;
    },
    success:      ( el, response ) => {
      let formResponse = contact_form.querySelector(
        '[data-element="response"]'
      );
      let formSuccess = contact_form.querySelector( '[data-response="success"]' );
      loader.classList.remove( "loader--show-in-form" );
      formSuccess.innerText = response.message;
      formSuccess.style.display = "block";
      setTimeout( () => {
        el.querySelector( '[name="first_name"]' ).value = "";
        el.querySelector( '[name="email"]' ).value = "";
        el.querySelector( '[name="message_title"]' ).value = "";
        el.querySelector( '[name="message"]' ).value = "";
        el.querySelector( "#daneOsobowe" ).checked = false;
        formResponse.style.display = "none";
      }, 6000 );
    },
    error:        ( el, response ) => {
      //console.log(response);
      let formResponse = contact_form.querySelector(
        '[data-element="response"]'
      );
      let formError = contact_form.querySelector( '[data-response="error"]' );
      loader.classList.remove( "loader--show-in-form" );
      formError.innerText = response;
      setTimeout( () => {
        formResponse.style.display = "none";
      }, 6000 );
    }
  } );
}
