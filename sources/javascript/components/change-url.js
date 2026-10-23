document.addEventListener("DOMContentLoaded", () => {
  const links = document.querySelectorAll(`.netivo-filters__category-title`);

  if (links.length) {
    links.forEach((link) => {
      if (!link.classList.contains("page-numbers")) {
        const lang = document.documentElement.lang;
        let parts = link.href.split("/");

        let domain = parts.slice(0, 3).join("/");
        let catalog = parts.slice(3, -1).join("/");

        if (lang === "en-US" || lang === "en-GB") {
          domain = parts.slice(0, 4).join("/");
          catalog = parts.slice(4, -1).join("/");
        }
        link.href = `${domain}/panel-b2b/${catalog.replace("sklep/", "")}/`;
      }
    });
  }
  const filters = document.querySelectorAll(
    `.netivo-filters__term .form__checkbox`,
  );

  if (filters.length) {
    filters.forEach((filter) => {
      if (!filter.classList.contains("page-numbers")) {
        if (filter.dataset.link.includes("/sklep/")) {
          filter.dataset.link = filter.dataset.link.replace(
            "/sklep/",
            "/panel-b2b/",
          );
        } else {
          const url = new URL(filter.dataset.link);
          url.pathname = "/panel-b2b" + url.pathname;
          filter.dataset.link = url.toString();
        }
      }
    });
  }

  const simple_links = document.querySelectorAll(
    ".woocommerce-mini-cart__buttons .button.wc-forward, .netivo-filters__back a, .woocommerce-breadcrumb a, .mobile-bar__contact",
  );
  simple_links.forEach((link) => {
    const parts = link.href.split("/");
    const lang = document.documentElement.lang;

    let domain = parts.slice(0, 3).join("/");
    let catalog = parts.slice(3, -1).join("/");
    if (lang === "en-US" || lang === "en-GB") {
      domain = parts.slice(0, 4).join("/");
      catalog = parts.slice(4, -1).join("/");
    }

    link.href = `${domain}/panel-b2b/${catalog}/`;
  });
});
