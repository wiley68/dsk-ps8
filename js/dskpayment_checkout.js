// Intercept-ваме клика върху payment option и изпращаме POST формата
(function () {
    let dskForm = null;
    let radio = null;
    let isSubmitting = false;

    function findDskPaymentElements() {
        // Намираме формата
        dskForm = document.getElementById('dskpayment-form');
        if (!dskForm) {
            return false;
        }

        // Намираме payment option контейнера за dskpayment - опитваме се с различни селектори
        let dskPaymentOption = document.querySelector('.payment-option[data-module-name="dskpayment"]');
        if (!dskPaymentOption) {
            // Опитваме се да намерим по logo или текст
            const paymentOptions = document.querySelectorAll('.payment-option');
            paymentOptions.forEach(function (option) {
                const logo = option.querySelector('img[src*="dskpayment"]');
                const text = option.textContent;
                if (logo || (text && text.indexOf('ДСК') !== -1)) {
                    dskPaymentOption = option;
                }
            });
        }

        if (!dskPaymentOption) {
            return false;
        }

        // Намираме radio бутона - опитваме се с различни селектори
        radio = dskPaymentOption.querySelector('input[type="radio"][name="payment-option"]');
        if (!radio) {
            radio = dskPaymentOption.querySelector('input[type="radio"]');
        }
        if (!radio) {
            return false;
        }

        return true;
    }

    function submitDskForm(e) {
        if (isSubmitting) {
            return false;
        }

        if (e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
        }

        // Проверяваме дали radio е избран и формата съществува
        if (radio && radio.checked && dskForm) {
            isSubmitting = true;
            dskForm.submit();
            return false;
        }
        return false;
    }

    function initDskPaymentForm() {
        if (!findDskPaymentElements()) {
            return false;
        }

        // Intercept-ваме промяната на radio бутона - използваме capture phase
        radio.addEventListener('change', function (e) {
            if (this.checked && !isSubmitting) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                setTimeout(function () {
                    submitDskForm(e);
                }, 50);
            }
        }, true); // true = capture phase

        // Intercept-ваме клика върху radio бутона - използваме capture phase
        radio.addEventListener('click', function (e) {
            if (!isSubmitting) {
                // Позволяваме на radio да се избере, но след това изпращаме формата
                setTimeout(function () {
                    if (radio.checked) {
                        submitDskForm(e);
                    }
                }, 50);
            }
        }, true); // true = capture phase

        // Intercept-ваме всички кликове в payment option контейнера
        const dskPaymentOption = radio.closest('.payment-option');
        if (dskPaymentOption) {
            dskPaymentOption.addEventListener('click', function (e) {
                if (isSubmitting) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }

                // Ако кликнем върху radio или label, не правим нищо (горните handlers ще се грижат)
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'LABEL') {
                    return;
                }

                // Проверяваме дали кликването е върху payment option контейнера
                const clickedElement = e.target.closest('.payment-option');
                if (clickedElement === dskPaymentOption) {
                    // Малко забавяне за да се избере radio бутонът
                    setTimeout(function () {
                        if (radio && radio.checked) {
                            submitDskForm(e);
                        }
                    }, 100);
                }
            }, true); // true = capture phase
        }

        // Intercept-ваме клика върху всички links в payment option
        if (dskPaymentOption) {
            const links = dskPaymentOption.querySelectorAll('a');
            links.forEach(function (link) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    if (radio && !isSubmitting) {
                        radio.checked = true;
                        setTimeout(function () {
                            submitDskForm(e);
                        }, 50);
                    }
                    return false;
                }, true);
            });
        }

        // Intercept-ваме формата на checkout страницата ако съществува
        const checkoutForm = document.querySelector('form#checkout, form[name="checkout"], #checkout-form');
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', function (e) {
                if (radio && radio.checked && !isSubmitting) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    submitDskForm(e);
                    return false;
                }
            }, true);
        }

        return true;
    }

    // Опитваме се да инициализираме веднага
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            // Опитваме се няколко пъти за да сме сигурни че DOM-ът е готов
            let attempts = 0;
            const maxAttempts = 20;
            const interval = setInterval(function () {
                attempts++;
                if (initDskPaymentForm() || attempts >= maxAttempts) {
                    clearInterval(interval);
                }
            }, 150);
        });
    } else {
        // DOM вече е готов
        setTimeout(function () {
            initDskPaymentForm();
        }, 100);
    }

    // Също опитваме се след пълно зареждане
    window.addEventListener('load', function () {
        setTimeout(function () {
            initDskPaymentForm();
        }, 300);
    });
})();