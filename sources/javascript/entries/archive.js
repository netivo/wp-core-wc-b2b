/* b2b_popup_vars */

document.addEventListener("DOMContentLoaded", () => {
  const products = document.querySelectorAll(
    ".b2b-content-loop-product .woocommerce-loop-product__title",
  );

  const popup = document.querySelector(".js-product-popup");
  const popup_content = document.querySelector(".js-product-popup__content");
  const popup_close = document.querySelector(".js-product-popup__close");

  products.forEach((product) => {
    product.addEventListener("click", (event) => {
      event.preventDefault();

      const product_id = product.closest(".b2b-content-loop-product").dataset
        .productId;

      fetch(b2b_popup_vars.ajax_url, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams({
          action: "get_product_details",
          id: product_id,
          nonce: b2b_popup_vars.security, // Pobierz wartość nonce
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          popup_content.innerHTML = data.data;
          popup.classList.add("product-popup--show");
        })
        .catch((error) => console.error("Error:", error));
    });

    popup_close.addEventListener("click", () => {
      popup.classList.remove("product-popup--show");
    });
  });

  const button = document.querySelector(".js-wp-block-search__close");
  const input = document.querySelector(".wp-block-search__input");

  if (button && input) {
    const form = button.closest("form");
    button.addEventListener("click", (event) => {
      event.preventDefault();
      input.value = "";
      form.submit();
    });
  }
});
