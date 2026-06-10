document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".quantity").forEach((quantityWrapper) => {
    const input = quantityWrapper.querySelector(".qty");
    const addToCartBtn = quantityWrapper.nextElementSibling;

    if (
      !input ||
      !addToCartBtn ||
      !addToCartBtn.classList.contains("ajax_add_to_cart")
    ) {
      return;
    }

    const sync = () => {
      addToCartBtn.dataset.quantity = input.value;
    };

    input.addEventListener("input", sync);
    input.addEventListener("change", sync);
    quantityWrapper
      .querySelector(".quantity__btn--plus")
      ?.addEventListener("click", () => setTimeout(sync, 0));
    quantityWrapper
      .querySelector(".quantity__btn--minus")
      ?.addEventListener("click", () => setTimeout(sync, 0));
  });
});
