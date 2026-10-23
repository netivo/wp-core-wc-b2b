document.addEventListener("DOMContentLoaded", () => {
  const invoiceCheckbox = document.querySelector("#fv_vat_1");
  const invoiceFields = document.querySelectorAll(".js-fvat-to-show");
  const receiptFields = document.querySelectorAll(".js-fvat-to-hide");
  const vatField = document.querySelector("#fv_vat_field");

  if (invoiceCheckbox) {
    invoiceCheckbox.checked = true;

    if (vatField) {
      vatField.style.display = "none";
    }
    invoiceFields.forEach((fld) => {
      setTimeout(() => {
        fld.style.display = "block";
      }, 1);
    });
    receiptFields.forEach((fld) => {
      setTimeout(() => {
        fld.style.display = "block";
      }, 1);
    });
  }
});
