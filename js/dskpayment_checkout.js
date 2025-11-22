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
});
