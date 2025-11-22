let old_vnoski;

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

function dskapi_pogasitelni_vnoski_input_focus(_old_vnoski) {
  old_vnoski = _old_vnoski;
}

function dskapi_pogasitelni_vnoski_input_change() {
  const dskapi_vnoski = parseFloat(
    document.getElementById('dskapi_pogasitelni_vnoski_input').value
  );
  const dskapi_price = parseFloat(
    document.getElementById('dskapi_price_txt').value
  );
  const dskapi_cid = document.getElementById('dskapi_cid').value;
  const DSKAPI_LIVEURL = document.getElementById('DSKAPI_LIVEURL').value;
  const dskapi_product_id = document.getElementById('dskapi_product_id').value;
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
          const dskapi_vnoska_input = document.getElementById('dskapi_vnoska');
          const dskapi_gpr = document.getElementById('dskapi_gpr');
          const dskapi_obshtozaplashtane_input = document.getElementById(
            'dskapi_obshtozaplashtane'
          );
          dskapi_vnoska_input.value = dsk_vnoska.toFixed(2);
          dskapi_gpr.value = dsk_gpr.toFixed(2);
          dskapi_obshtozaplashtane_input.value = (
            dsk_vnoska * dskapi_vnoski
          ).toFixed(2);
          old_vnoski = dskapi_vnoski;
        } else {
          alert('Избраният брой погасителни вноски е под минималния.');
          var dskapi_vnoski_input = document.getElementById(
            'dskapi_pogasitelni_vnoski_input'
          );
          dskapi_vnoski_input.value = old_vnoski;
        }
      } else {
        alert('Избраният брой погасителни вноски е над максималния.');
        var dskapi_vnoski_input = document.getElementById(
          'dskapi_pogasitelni_vnoski_input'
        );
        dskapi_vnoski_input.value = old_vnoski;
      }
    }
  };
  xmlhttpro.send();
}

function initDskapiWidget() {
  const btn_dskapi = document.getElementById('btn_dskapi');
  if (btn_dskapi !== null && btn_dskapi.dataset.dskapiBound !== '1') {
    btn_dskapi.dataset.dskapiBound = '1';
    const dskapi_button_status = parseInt(
      document.getElementById('dskapi_button_status').value
    );
    const dskapiProductPopupContainer = document.getElementById(
      'dskapi-product-popup-container'
    );
    const dskapi_back_credit = document.getElementById('dskapi_back_credit');
    const dskapi_buy_credit = document.getElementById('dskapi_buy_credit');
    const dskapi_buy_buttons_submit = document.querySelectorAll(
      'button[data-button-action=add-to-cart]'
    );

    const dskapi_price = document.getElementById('dskapi_price');
    const dskapi_maxstojnost = document.getElementById('dskapi_maxstojnost');
    let dskapi_price1 = dskapi_price.value;
    let dskapi_quantity = 1;
    let dskapi_priceall = parseFloat(dskapi_price1) * dskapi_quantity;

    btn_dskapi.addEventListener('click', (event) => {
      if (dskapi_button_status == 1) {
        console.log('Директно към поръчката с извикан платежен метод');
      } else {
        //get price with options
        let el_dskapi_price1 = document.querySelector(
          'span.current-price-value'
        );
        if (el_dskapi_price1 !== null) {
          dskapi_price1 = el_dskapi_price1.getAttribute('content');
        } else {
          el_dskapi_price1 = document.querySelector('[itemprop=price]');
          if (el_dskapi_price1 !== null) {
            el_dskapi_price1 = el_dskapi_price1.innerHTML.replace(
              /[^\d,-]/g,
              ''
            );
            if (el_dskapi_price1 !== null) {
              if (el_dskapi_price1.indexOf('.') !== -1) {
                dskapi_price1 = el_dskapi_price1.replace(/[^\d.-]/g, '');
              } else {
                dskapi_price1 = el_dskapi_price1.replace(/,/g, '.');
              }
            }
          }
        }
        if (document.getElementsByName('qty') !== null) {
          dskapi_quantity = parseFloat(
            document.getElementsByName('qty')[0].value
          );
        }
        dskapi_priceall = parseFloat(dskapi_price1) * dskapi_quantity;

        const dskapi_eur = parseInt(
          document.getElementById('dskapi_eur').value
        );
        const dskapi_currency_code = document.getElementById(
          'dskapi_currency_code'
        ).value;
        switch (dskapi_eur) {
          case 0:
            break;
          case 1:
            if (dskapi_currency_code == 'EUR') {
              dskapi_priceall = dskapi_priceall * 1.95583;
            }
            break;
          case 2:
          case 3:
            if (dskapi_currency_code == 'BGN') {
              dskapi_priceall = dskapi_priceall / 1.95583;
            }
            break;
        }

        const dskapi_price_txt = document.getElementById('dskapi_price_txt');
        dskapi_price_txt.value = dskapi_priceall.toFixed(2);
        if (dskapi_priceall <= parseFloat(dskapi_maxstojnost.value)) {
          dskapiProductPopupContainer.style.display = 'block';
          dskapi_pogasitelni_vnoski_input_change();
        } else {
          alert(
            'Максимално позволената цена за кредит ' +
              parseFloat(dskapi_maxstojnost.value).toFixed(2) +
              ' е надвишена!'
          );
        }
      }
    });

    dskapi_back_credit.addEventListener('click', (event) => {
      dskapiProductPopupContainer.style.display = 'none';
    });

    dskapi_buy_credit.addEventListener('click', (event) => {
      event.preventDefault();
      dskapiProductPopupContainer.style.display = 'none';

      // Функция за записване на cookie
      const setCookie = function (name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
        document.cookie =
          name +
          '=' +
          value +
          ';expires=' +
          expires.toUTCString() +
          ';path=/;SameSite=Lax';
      };

      // Записваме избора на платежен метод в cookie (валиден за 1 час)
      setCookie('dskpayment_selected', '1', 1 / 24);

      // Намираме стандартния бутон за добавяне в количката
      const addToCartButton = document.querySelector(
        'button[data-button-action=add-to-cart]'
      );

      if (addToCartButton) {
        // Регистрираме event listener преди да кликнем на бутона
        let cartUpdated = false;
        const checkoutRedirect = function () {
          if (!cartUpdated) {
            cartUpdated = true;
            const checkoutUrl = document.getElementById(
              'dskapi_checkout_url'
            ).value;
            if (checkoutUrl) {
              window.location.href = checkoutUrl;
            }
          }
        };

        // Слушаме за успешно добавяне в количката
        if (typeof prestashop !== 'undefined' && prestashop.on) {
          prestashop.on('updateCart', checkoutRedirect);
          prestashop.on('updatedCart', checkoutRedirect);
        }

        // Добавяме продукта в количката чрез кликване на стандартния бутон
        addToCartButton.click();

        // Fallback: ако след 1 секунда не е настъпило събитие, редиректваме
        setTimeout(checkoutRedirect, 1000);
      } else {
        // Ако няма стандартен бутон, редиректваме директно към чекаута
        const checkoutUrl = document.getElementById(
          'dskapi_checkout_url'
        ).value;
        if (checkoutUrl) {
          window.location.href = checkoutUrl;
        }
      }
    });
  }
}

document.addEventListener('DOMContentLoaded', initDskapiWidget);
if (typeof prestashop !== 'undefined' && prestashop.on) {
  prestashop.on('updatedProduct', initDskapiWidget);
  prestashop.on('updatedProductCombination', initDskapiWidget);
  prestashop.on('updatedProductAttributes', initDskapiWidget);
}
