let old_vnoski;

function createCORSRequest(method, url) {
  var xhr = new XMLHttpRequest();
  if ("withCredentials" in xhr) {
    xhr.open(method, url, true);
  } else if (typeof XDomainRequest != "undefined") {
    xhr = new XDomainRequest();
    xhr.open(method, url);
  } else {
    xhr = null;
  }
  return xhr;
}

function dskapi_pogasitelni_vnoski_input_focus(_old_vnoski) {
  old_vnoski = _old_vnoski;
}

function dskapi_pogasitelni_vnoski_input_change(silent = false) {
  const dskapi_vnoski = parseFloat(
    document.getElementById("dskapi_pogasitelni_vnoski_input").value
  );
  const dskapi_price_txt_el = document.getElementById("dskapi_price_txt");
  let dskapi_price = parseDskapiPriceValue(
    dskapi_price_txt_el ? dskapi_price_txt_el.value : null
  );
  const totalFromPrestashop = getDskapiCartTotalFromPrestashop();
  const totalFromDom = getDskapiCartTotalFromDom();
  const total =
    totalFromPrestashop !== null ? totalFromPrestashop : totalFromDom;
  if (total !== null) {
    dskapi_price = total;
    if (dskapi_price_txt_el) {
      dskapi_price_txt_el.value = total.toFixed(2);
    }
    const dskapi_price_hidden = document.getElementById("dskapi_price");
    if (dskapi_price_hidden) {
      dskapi_price_hidden.value = total.toFixed(2);
    }
  }
  if (dskapi_price === null) {
    return;
  }
  const dskapi_cid = document.getElementById("dskapi_cid").value;
  const DSKAPI_LIVEURL = document.getElementById("DSKAPI_LIVEURL").value;
  const dskapi_product_id = document.getElementById("dskapi_product_id").value;
  var xmlhttpro = createCORSRequest(
    "GET",
    DSKAPI_LIVEURL +
      "/function/getproductcustom.php?cid=" +
      dskapi_cid +
      "&price=" +
      dskapi_price +
      "&product_id=" +
      dskapi_product_id +
      "&dskapi_vnoski=" +
      dskapi_vnoski
  );
  xmlhttpro.onreadystatechange = function () {
    if (this.readyState == 4) {
      var options = JSON.parse(this.response).dsk_options;
      var dsk_vnoska = parseFloat(JSON.parse(this.response).dsk_vnoska);
      var dsk_gpr = parseFloat(JSON.parse(this.response).dsk_gpr);
      var dsk_is_visible = JSON.parse(this.response).dsk_is_visible;
      const dskapi_vnoski_label = document.getElementById(
        "dskapi_vnoski_label"
      );
      const dskapi_vnoska_label = document.getElementById(
        "dskapi_vnoska_label"
      );
      const dskapi_vnoski_txt = document.getElementById("dskapi_vnoski_txt");
      const dskapi_vnoska_txt = document.getElementById("dskapi_vnoska_txt");
      if (dsk_is_visible) {
        if (options) {
          const dskapi_vnoska_input = document.getElementById("dskapi_vnoska");
          const dskapi_gpr = document.getElementById("dskapi_gpr");
          const dskapi_obshtozaplashtane_input = document.getElementById(
            "dskapi_obshtozaplashtane"
          );
          dskapi_vnoska_input.value = dsk_vnoska.toFixed(2);
          dskapi_gpr.value = dsk_gpr.toFixed(2);
          dskapi_obshtozaplashtane_input.value = (
            dsk_vnoska * dskapi_vnoski
          ).toFixed(2);
          old_vnoski = dskapi_vnoski;
          if (dskapi_vnoski_label) {
            dskapi_vnoski_label.textContent = dskapi_vnoski;
          }
          if (dskapi_vnoska_label) {
            dskapi_vnoska_label.textContent = dsk_vnoska.toFixed(2);
          }
          if (dskapi_vnoski_txt) {
            dskapi_vnoski_txt.textContent = dskapi_vnoski;
          }
          if (dskapi_vnoska_txt) {
            dskapi_vnoska_txt.textContent = dsk_vnoska.toFixed(2);
          }
        } else {
          if (!silent) {
            alert("Избраният брой погасителни вноски е под минималния.");
          }
          var dskapi_vnoski_input = document.getElementById(
            "dskapi_pogasitelni_vnoski_input"
          );
          dskapi_vnoski_input.value = old_vnoski;
          if (dskapi_vnoski_label) {
            dskapi_vnoski_label.textContent = old_vnoski;
          }
          if (dskapi_vnoski_txt) {
            dskapi_vnoski_txt.textContent = old_vnoski;
          }
        }
      } else {
        if (!silent) {
          alert("Избраният брой погасителни вноски е над максималния.");
        }
        var dskapi_vnoski_input = document.getElementById(
          "dskapi_pogasitelni_vnoski_input"
        );
        dskapi_vnoski_input.value = old_vnoski;
        if (dskapi_vnoski_label) {
          dskapi_vnoski_label.textContent = old_vnoski;
        }
        if (dskapi_vnoski_txt) {
          dskapi_vnoski_txt.textContent = old_vnoski;
        }
      }
    }
  };
  xmlhttpro.send();
}

/**
 * Добавя продукта в количката и пренасочва към checkout с избран платежен метод DSK
 * За използване в cart страницата - директно редирект към checkout без добавяне в количката
 *
 * @return {void}
 */
function dskapi_redirectToCheckoutWithPaymentMethod() {
  // Функция за записване на cookie
  const setCookie = function (name, value, days) {
    const expires = new Date();
    expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
    document.cookie =
      name +
      "=" +
      value +
      ";expires=" +
      expires.toUTCString() +
      ";path=/;SameSite=Lax";
  };

  // Записваме избора на платежен метод в cookie (валиден за 1 час)
  setCookie("dskpayment_selected", "1", 1 / 24);

  // Редиректваме директно към checkout (в cart страницата продуктът вече е в количката)
  const checkoutUrl = document.getElementById("dskapi_checkout_url");
  if (checkoutUrl && checkoutUrl.value) {
    window.location.href = checkoutUrl.value;
  } else {
    // Fallback: опитваме се да намерим checkout URL по друг начин
    const checkoutLink = document.querySelector('a[href*="order"]');
    if (checkoutLink) {
      window.location.href = checkoutLink.href;
    }
  }
}

// Делегиране на събития за бутона "Купи на изплащане" - използваме document-level listener
// Това работи дори ако елементите се зареждат динамично и се изпълнява само веднъж
let dskapiBuyCreditHandlerBound = false;

function initDskapiCartWidget() {
  // Делегиране на събития за бутона "Купи на изплащане" - само веднъж
  if (!dskapiBuyCreditHandlerBound) {
    dskapiBuyCreditHandlerBound = true;
    document.addEventListener(
      "click",
      function (event) {
        const target = event.target;
        // Проверяваме дали кликването е върху бутона или неговите деца
        if (
          target &&
          (target.id === "dskapi_buy_credit" ||
            target.closest("#dskapi_buy_credit"))
        ) {
          event.preventDefault();
          event.stopPropagation();
          event.stopImmediatePropagation();

          const dskapiProductPopupContainer = document.getElementById(
            "dskapi-product-popup-container"
          );
          if (dskapiProductPopupContainer) {
            dskapiProductPopupContainer.style.display = "none";
          }

          dskapi_redirectToCheckoutWithPaymentMethod();
          return false;
        }
      },
      true
    ); // Използваме capture phase за по-ранно intercept-ване
  }

  // Задаваме cursor стил на бутона ако съществува
  const dskapi_buy_credit = document.getElementById("dskapi_buy_credit");
  if (dskapi_buy_credit !== null) {
    dskapi_buy_credit.style.cursor = "pointer";
  }

  // Инициализираме основния бутон btn_dskapi
  const btn_dskapi = document.getElementById("btn_dskapi");
  if (btn_dskapi !== null && btn_dskapi.dataset.dskapiBound !== "1") {
    btn_dskapi.dataset.dskapiBound = "1";

    const dskapi_button_status_el = document.getElementById(
      "dskapi_button_status"
    );
    if (!dskapi_button_status_el) {
      return; // Ако няма button_status елемент, не продължаваме
    }

    const dskapi_button_status = parseInt(dskapi_button_status_el.value) || 0;
    const dskapiProductPopupContainer = document.getElementById(
      "dskapi-product-popup-container"
    );
    const dskapi_back_credit = document.getElementById("dskapi_back_credit");

    const dskapi_price = document.getElementById("dskapi_price");
    const dskapi_maxstojnost = document.getElementById("dskapi_maxstojnost");

    if (!dskapi_price || !dskapi_maxstojnost) {
      return; // Ако няма нужните елементи, не продължаваме
    }

    let dskapi_price1 = dskapi_price.value;
    let dskapi_quantity = 1;
    let dskapi_priceall = parseFloat(dskapi_price1) * dskapi_quantity;

    btn_dskapi.addEventListener(
      "click",
      (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (dskapi_button_status == 1) {
          dskapi_redirectToCheckoutWithPaymentMethod();
          return false;
        } else {
          updateDskapiCartTotal();
          const dskapi_eur_el = document.getElementById("dskapi_eur");
          const dskapi_currency_code_el = document.getElementById(
            "dskapi_currency_code"
          );

          if (!dskapi_eur_el || !dskapi_currency_code_el) {
            return false;
          }

          const dskapi_eur = parseInt(dskapi_eur_el.value) || 0;
          const dskapi_currency_code = dskapi_currency_code_el.value;

          switch (dskapi_eur) {
            case 0:
              break;
            case 1:
              if (dskapi_currency_code == "EUR") {
                dskapi_priceall = dskapi_priceall * 1.95583;
              }
              break;
            case 2:
            case 3:
              if (dskapi_currency_code == "BGN") {
                dskapi_priceall = dskapi_priceall / 1.95583;
              }
              break;
          }

          const dskapi_price_txt = document.getElementById("dskapi_price_txt");
          if (dskapi_price_txt) {
            dskapi_price_txt.value = dskapi_priceall.toFixed(2);
          }

          if (dskapi_priceall <= parseFloat(dskapi_maxstojnost.value)) {
            if (dskapiProductPopupContainer) {
              dskapiProductPopupContainer.style.display = "block";
              refreshDskapiPopupTotals(false);
            }
          } else {
            alert(
              "Максимално позволената цена за кредит " +
                parseFloat(dskapi_maxstojnost.value).toFixed(2) +
                " е надвишена!"
            );
          }
        }
        return false;
      },
      true
    ); // Използваме capture phase

    if (dskapi_back_credit) {
      dskapi_back_credit.addEventListener(
        "click",
        (event) => {
          event.preventDefault();
          if (dskapiProductPopupContainer) {
            dskapiProductPopupContainer.style.display = "none";
          }
          return false;
        },
        true
      );
    }
  }
}

// Функция за инициализация с няколко опита
function initDskapiCartWidgetWithRetry() {
  let attempts = 0;
  const maxAttempts = 10;

  const tryInit = function () {
    attempts++;

    // Проверяваме дали нужните елементи съществуват
    const btn_dskapi = document.getElementById("btn_dskapi");
    const dskapi_buy_credit = document.getElementById("dskapi_buy_credit");
    const dskapi_button_status = document.getElementById(
      "dskapi_button_status"
    );

    if (btn_dskapi || dskapi_buy_credit || dskapi_button_status) {
      initDskapiCartWidget();
    } else if (attempts < maxAttempts) {
      setTimeout(tryInit, 200);
    }
  };

  tryInit();
}

function parseDskapiPriceValue(rawValue) {
  if (rawValue === null || rawValue === undefined) {
    return null;
  }
  if (typeof rawValue === "number") {
    return isNaN(rawValue) ? null : rawValue;
  }
  if (typeof rawValue === "string") {
    const normalized = rawValue.replace(/[^\d.,-]/g, "").replace(",", ".");
    const parsed = parseFloat(normalized);
    return isNaN(parsed) ? null : parsed;
  }
  return null;
}

function getDskapiCartTotalFromEvent(event) {
  const resp = event && (event.resp || event);
  const totals = resp && resp.cart && resp.cart.totals;
  if (!totals) {
    return null;
  }

  const candidates = [
    totals.total && totals.total.amount,
    totals.total_including_tax && totals.total_including_tax.amount,
    totals.total && totals.total.value,
    totals.total_including_tax && totals.total_including_tax.value,
  ];

  for (let i = 0; i < candidates.length; i++) {
    const value = parseDskapiPriceValue(candidates[i]);
    if (value !== null) {
      return value;
    }
  }

  return null;
}

function getDskapiCartTotalFromPrestashop() {
  if (typeof prestashop === "undefined" || !prestashop.cart) {
    return null;
  }
  const totals = prestashop.cart.totals || null;
  if (!totals) {
    return null;
  }

  const candidates = [
    totals.total && totals.total.amount,
    totals.total_including_tax && totals.total_including_tax.amount,
    totals.total && totals.total.value,
    totals.total_including_tax && totals.total_including_tax.value,
  ];

  for (let i = 0; i < candidates.length; i++) {
    const value = parseDskapiPriceValue(candidates[i]);
    if (value !== null) {
      return value;
    }
  }

  return null;
}

function getDskapiCartTotalFromDom() {
  const selectors = [
    ".cart-summary-totals .cart-total .value",
    ".cart-summary-totals .cart-total .price",
    ".cart-summary-totals .value",
    ".cart-summary-line.cart-total .value",
    ".cart-summary-line.cart-total .price",
    ".cart-total .value",
    ".cart-total .price",
    ".cart-summary-line .value",
    ".cart-summary-line .price",
  ];

  for (let i = 0; i < selectors.length; i++) {
    const el = document.querySelector(selectors[i]);
    if (el && el.textContent) {
      const value = parseDskapiPriceValue(el.textContent);
      if (value !== null) {
        return value;
      }
    }
  }

  return null;
}

function updateDskapiCartTotal(event) {
  const totalFromEvent = getDskapiCartTotalFromEvent(event);
  const totalFromPrestashop = getDskapiCartTotalFromPrestashop();
  const total =
    totalFromEvent !== null
      ? totalFromEvent
      : totalFromPrestashop !== null
      ? totalFromPrestashop
      : getDskapiCartTotalFromDom();
  if (total === null) {
    return;
  }

  const dskapi_price = document.getElementById("dskapi_price");
  if (dskapi_price) {
    dskapi_price.value = total.toFixed(2);
  }

  const dskapi_price_txt = document.getElementById("dskapi_price_txt");
  if (dskapi_price_txt) {
    dskapi_price_txt.value = total.toFixed(2);
  }

  refreshDskapiPopupTotals(true);
}

function refreshDskapiPopupTotals(silent = false) {
  const dskapi_vnoski_input = document.getElementById(
    "dskapi_pogasitelni_vnoski_input"
  );
  if (!dskapi_vnoski_input) {
    return;
  }

  if (
    typeof old_vnoski === "undefined" ||
    old_vnoski === null ||
    old_vnoski === ""
  ) {
    old_vnoski = dskapi_vnoski_input.value;
  }

  dskapi_pogasitelni_vnoski_input_change(silent);
}

const dskapiCartTotalObserverState = {
  observer: null,
  lastValue: null,
};

function initDskapiCartTotalObserver() {
  const selectors = [
    ".cart-summary-totals .value",
    ".cart-summary-line.cart-total .value",
    ".cart-summary-line .value",
  ];

  let targetEl = null;
  for (let i = 0; i < selectors.length; i++) {
    const el = document.querySelector(selectors[i]);
    if (el) {
      targetEl = el;
      break;
    }
  }

  if (!targetEl) {
    return false;
  }

  if (dskapiCartTotalObserverState.observer) {
    dskapiCartTotalObserverState.observer.disconnect();
  }

  dskapiCartTotalObserverState.lastValue = parseDskapiPriceValue(
    targetEl.textContent || ""
  );

  dskapiCartTotalObserverState.observer = new MutationObserver(function () {
    const value = parseDskapiPriceValue(targetEl.textContent || "");
    if (value !== null && value !== dskapiCartTotalObserverState.lastValue) {
      dskapiCartTotalObserverState.lastValue = value;
      updateDskapiCartTotal();
    }
  });

  dskapiCartTotalObserverState.observer.observe(targetEl, {
    childList: true,
    characterData: true,
    subtree: true,
  });

  return true;
}

function initDskapiCartTotalObserverWithRetry() {
  let attempts = 0;
  const maxAttempts = 10;

  const tryInit = function () {
    attempts++;
    if (!initDskapiCartTotalObserver() && attempts < maxAttempts) {
      setTimeout(tryInit, 200);
    }
  };

  tryInit();
}

document.addEventListener("DOMContentLoaded", function () {
  initDskapiCartWidgetWithRetry();
  updateDskapiCartTotal();
  initDskapiCartTotalObserverWithRetry();
});

if (typeof prestashop !== "undefined" && prestashop.on) {
  prestashop.on("updatedCart", function (event) {
    updateDskapiCartTotal(event);
    initDskapiCartTotalObserverWithRetry();
    setTimeout(initDskapiCartWidgetWithRetry, 100);
  });
  prestashop.on("updateCart", function (event) {
    updateDskapiCartTotal(event);
    initDskapiCartTotalObserverWithRetry();
    setTimeout(initDskapiCartWidgetWithRetry, 100);
  });
}

// Също опитваме се след пълно зареждане на страницата
window.addEventListener("load", function () {
  setTimeout(initDskapiCartWidgetWithRetry, 300);
  setTimeout(initDskapiCartTotalObserverWithRetry, 300);
});
