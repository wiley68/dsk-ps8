let old_vnoski_checkout;

function createCORSRequest(method, url) {
  var xhr = new XMLHttpRequest();
  if ('withCredentials' in xhr) {
    xhr.open(method, url, true);
  } else if (typeof XDomainRequest != 'undefined') {
    xhr = new XDomainRequest();
    xhr.open(method, url);
  } else {
    xhr = null;
  }
  return xhr;
}

function dskapi_checkout_pogasitelni_vnoski_input_focus(_old_vnoski) {
  old_vnoski_checkout = _old_vnoski;
}

function dskapi_checkout_pogasitelni_vnoski_input_change() {
  const dskapi_vnoski = parseFloat(
    document.getElementById('dskapi_checkout_pogasitelni_vnoski_input').value
  );
  const dskapi_price = parseFloat(
    document.getElementById('dskapi_checkout_price_txt').value
  );
  const dskapi_cid = document.getElementById('dskapi_checkout_cid').value;
  const DSKAPI_LIVEURL = document.getElementById('dskapi_checkout_DSKAPI_LIVEURL').value;
  const dskapi_product_id = document.getElementById('dskapi_checkout_product_id').value;
  var xmlhttpro = createCORSRequest(
    'GET',
    DSKAPI_LIVEURL +
    '/function/getproductcustom.php?cid=' +
    dskapi_cid +
    '&price=' +
    dskapi_price +
    '&product_id=' +
    dskapi_product_id +
    '&dskapi_vnoski=' +
    dskapi_vnoski
  );
  xmlhttpro.onreadystatechange = function () {
    if (this.readyState == 4) {
      var options = JSON.parse(this.response).dsk_options;
      var dsk_vnoska = parseFloat(JSON.parse(this.response).dsk_vnoska);
      var dsk_gpr = parseFloat(JSON.parse(this.response).dsk_gpr);
      var dsk_is_visible = JSON.parse(this.response).dsk_is_visible;
      if (dsk_is_visible) {
        if (options) {
          const dskapi_vnoska_input = document.getElementById('dskapi_checkout_vnoska');
          const dskapi_gpr = document.getElementById('dskapi_checkout_gpr');
          const dskapi_obshtozaplashtane_input = document.getElementById(
            'dskapi_checkout_obshtozaplashtane'
          );
          dskapi_vnoska_input.value = dsk_vnoska.toFixed(2);
          dskapi_gpr.value = dsk_gpr.toFixed(2);
          dskapi_obshtozaplashtane_input.value = (
            dsk_vnoska * dskapi_vnoski
          ).toFixed(2);
          old_vnoski_checkout = dskapi_vnoski;
        } else {
          alert('Избраният брой погасителни вноски е под минималния.');
          var dskapi_vnoski_input = document.getElementById(
            'dskapi_checkout_pogasitelni_vnoski_input'
          );
          dskapi_vnoski_input.value = old_vnoski_checkout;
        }
      } else {
        alert('Избраният брой погасителни вноски е над максималния.');
        var dskapi_vnoski_input = document.getElementById(
          'dskapi_checkout_pogasitelni_vnoski_input'
        );
        dskapi_vnoski_input.value = old_vnoski_checkout;
      }
    }
  };
  xmlhttpro.send();
}

jQuery(document).ready(function ($) {
  // Функция за четене на cookie
  const getCookie = function (name) {
    const nameEQ = name + '=';
    const ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
      let c = ca[i];
      while (c.charAt(0) === ' ') c = c.substring(1, c.length);
      if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
  };

  // Функция за изтриване на cookie
  const deleteCookie = function (name) {
    document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;';
  };

  // Функция за обработка на избора на платежен метод
  const handlePaymentSelection = function (isDskPayment, event) {
    if (isDskPayment) {
      if (event) {
        event.stopImmediatePropagation();
      }
      $('form#conditions-to-approve').hide();
      $('div#payment-confirmation').removeClass('js-payment-confirmation');
      $('div#payment-confirmation').hide();
    } else {
      // Ако потребителят избере друг платежен метод, изтриваме cookie-то
      deleteCookie('dskpayment_selected');
      $('form#conditions-to-approve').show();
      $('div#payment-confirmation').addClass('js-payment-confirmation');
      $('div#payment-confirmation').show();
    }
  };

  // Автоматично избиране на платежния метод, ако е зададено в cookie
  const dskPaymentRadio = $(
    'input[type="radio"][name="payment-option"][data-module-name="dskpayment"]'
  );
  const dskPaymentSelected = getCookie('dskpayment_selected') === '1';

  // Проверяваме дали платежният метод вече е избран
  const isAlreadySelected =
    dskPaymentRadio.length > 0 && dskPaymentRadio.is(':checked');

  if (dskPaymentSelected && !isAlreadySelected && dskPaymentRadio.length > 0) {
    // Избираме платежния метод само ако не е вече избран
    dskPaymentRadio.prop('checked', true);

    // Изчакваме малко за да се уверя, че DOM и PrestaShop са готови
    setTimeout(function () {
      // Задействаме оригиналното събитие на PrestaShop за показване на съдържанието
      dskPaymentRadio.trigger('change');

      // Извикваме директно функцията за показване/скриване на данните
      handlePaymentSelection(true, null);
    }, 200);
  }

  $(document.body).on(
    'click',
    'input[type="radio"][name="payment-option"]',
    function (event) {
      const isDskPayment = $(this).attr('data-module-name') === 'dskpayment';
      handlePaymentSelection(isDskPayment, event);
    }
  );

  // Слушаме и за change събитието, за да работи с PrestaShop логиката
  $(document.body).on(
    'change',
    'input[type="radio"][name="payment-option"]',
    function (event) {
      const isDskPayment = $(this).attr('data-module-name') === 'dskpayment';
      if (isDskPayment) {
        handlePaymentSelection(true, event);
      }
    }
  );

  // Инициализация на попъпа за лихвени схеми
  const initCheckoutPopup = function () {
    const interestRatesLink = document.getElementById('dskapi_checkout_interest_rates_link');
    const popupContainer = document.getElementById('dskapi-checkout-popup-container');
    const closePopupBtn = document.getElementById('dskapi_checkout_close_popup');

    if (interestRatesLink && popupContainer) {
      // Отваряне на попъпа при кликване на линка
      interestRatesLink.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();

        if (popupContainer) {
          popupContainer.style.display = 'block';
          // Извикваме функцията за изчисляване на вноските при отваряне
          const vnoskiInput = document.getElementById('dskapi_checkout_pogasitelni_vnoski_input');
          if (vnoskiInput) {
            dskapi_checkout_pogasitelni_vnoski_input_change();
          }
        }
        return false;
      });

      // Затваряне на попъпа при кликване на бутона "Затвори"
      if (closePopupBtn) {
        closePopupBtn.addEventListener('click', function (event) {
          event.preventDefault();
          event.stopPropagation();

          if (popupContainer) {
            popupContainer.style.display = 'none';
          }
          return false;
        });
      }

      // Затваряне на попъпа при кликване извън него
      if (popupContainer) {
        popupContainer.addEventListener('click', function (event) {
          if (event.target === popupContainer) {
            popupContainer.style.display = 'none';
          }
        });
      }
    }
  };

  // Инициализираме попъпа след зареждане на DOM
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCheckoutPopup);
  } else {
    initCheckoutPopup();
  }

  // Опитваме се да инициализираме и след пълно зареждане на страницата
  window.addEventListener('load', function () {
    setTimeout(initCheckoutPopup, 300);
  });
});
