document.addEventListener("DOMContentLoaded", () => {
  const btn = document.querySelector(".js-b2b-checkout-btn");
  if (!btn) return;

  document.addEventListener("netivo:cart:updated", (e) => {
    btn.classList.toggle("button--disabled", e.detail.count === 0);
  });
});
