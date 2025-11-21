jQuery(document).ready(function ($) {
  $(document.body).on(
    'click',
    'input[type="radio"][name="payment-option"]',
    function (event) {
      if ($(this).attr('data-module-name') === 'dskpayment') {
        event.stopImmediatePropagation();
        $('form#conditions-to-approve').hide();
        $('div#payment-confirmation').removeClass('js-payment-confirmation');
        $('div#payment-confirmation').hide();
      } else {
        $('form#conditions-to-approve').show();
        $('div#payment-confirmation').addClass('js-payment-confirmation');
        $('div#payment-confirmation').show();
      }
    }
  );
});
