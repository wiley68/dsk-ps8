<?php

/**
 * 2007-2024 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2024 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class DskPaymentValidationModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        // Валидираме CSRF token
        $receivedToken = Tools::getValue('token', '');
        $cartId = (int) $this->context->cart->id;

        if (empty($cartId)) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1
                ]
            ));
        }

        $customer = new Customer($this->context->cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1
                ]
            ));
        }

        $expectedToken = md5($cartId . '_' . $customer->secure_key . '_' . Configuration::get('PS_COOKIE_CHECKSUM'));

        if (empty($receivedToken) || $receivedToken !== $expectedToken) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1
                ]
            ));
        }

        // При POST заявки извикваме processOrder()
        $this->processOrder();
    }

    /**
     * Основна логика за обработка на поръчката
     */
    private function processOrder()
    {
        $dskapi_firstname = filter_var(Tools::getValue('dskapi_firstname', ''), FILTER_SANITIZE_SPECIAL_CHARS);
        $dskapi_lastname = filter_var(Tools::getValue('dskapi_lastname', ''), FILTER_SANITIZE_SPECIAL_CHARS);
        $dskapi_phone = filter_var(Tools::getValue('dskapi_phone', ''), FILTER_SANITIZE_SPECIAL_CHARS);
        $dskapi_email = filter_var(Tools::getValue('dskapi_email', ''), FILTER_SANITIZE_EMAIL);
        $dskapi_address2 = filter_var(Tools::getValue('dskapi_address2', ''), FILTER_SANITIZE_SPECIAL_CHARS);
        $dskapi_address2city = filter_var(Tools::getValue('dskapi_address2city', ''), FILTER_SANITIZE_SPECIAL_CHARS);
        $dskapi_address1 = filter_var(Tools::getValue('dskapi_address1', ''), FILTER_SANITIZE_SPECIAL_CHARS);
        $dskapi_address1city = filter_var(Tools::getValue('dskapi_address1city', ''), FILTER_SANITIZE_SPECIAL_CHARS);
        $dskapi_postcode = filter_var(Tools::getValue('dskapi_postcode', ''), FILTER_SANITIZE_SPECIAL_CHARS);
        $dskapi_eur = filter_var(Tools::getValue('dskapi_eur', ''), FILTER_SANITIZE_NUMBER_INT);

        // Валидация на задължителни полета
        if (empty($dskapi_firstname) || empty($dskapi_lastname) || empty($dskapi_email) || empty($dskapi_phone) || empty($dskapi_address1) || empty($dskapi_address1city)) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1
                ]
            ));
        }

        // Валидация на email адрес
        if (!filter_var($dskapi_email, FILTER_VALIDATE_EMAIL)) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1
                ]
            ));
        }

        if (false === $this->checkIfContextIsValid() || false === $this->checkIfPaymentOptionIsAvailable()) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1
                ]
            ));
        }

        $customer = new Customer($this->context->cart->id_customer);
        if (false === Validate::isLoadedObject($customer)) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1
                ]
            ));
        }

        $dskapi_total = (float) $this->context->cart->getOrderTotal(true, Cart::BOTH);
        $cart = $this->context->cart;

        // Проверка дали кошницата има продукти
        $products = $cart->getProducts(true);
        if (empty($products)) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1
                ]
            ));
        }

        // Проверка за конфигуриран DSKAPI_CID
        $dskapi_cid = (string) Configuration::get('DSKAPI_CID');
        if (empty($dskapi_cid)) {
            die($this->module->l('Модулът не е конфигуриран правилно. Моля свържете се с администратора.', 'validation'));
        }

        $currencySpecial = (int) $this->context->currency->id;
        /** @var Currency|int|null $currencySpecial */
        $this->module->validateOrder(
            (int) $cart->id,
            Configuration::get('PS_OS_DSKPAYMENT'),
            $dskapi_total,
            $this->module->displayName,
            null,
            [],
            $currencySpecial,
            false,
            $customer->secure_key
        );

        $dskapi_type_client = $this->detectMobileDevice();

        $dskapi_currency_code = $this->context->currency->iso_code;
        $dskapi_currency_code_send = 0;

        switch ($dskapi_eur) {
            case 0:
                break;
            case 1:
                $dskapi_currency_code_send = 0;
                if ($dskapi_currency_code == "EUR") {
                    $dskapi_total = number_format($dskapi_total * 1.95583, 2, ".", "");
                }
                break;
            case 2:
                $dskapi_currency_code_send = 1;
                if ($dskapi_currency_code == "BGN") {
                    $dskapi_total = number_format($dskapi_total / 1.95583, 2, ".", "");
                }
                break;
        }

        $products_id = '';
        $products_q = '';
        $products_p = '';
        $products_name = '';
        $products_c = '';
        $products_m = '';
        $products_i = '';
        foreach ($products as $product) {
            $products_id .= strval($product['id_product']);
            $products_id .= '_';
            $products_q .= strval($product['quantity']);
            $products_q .= '_';

            $products_p_temp = (float) $product['price_wt'];
            switch ($dskapi_eur) {
                case 0:
                    break;
                case 1:
                    if ($dskapi_currency_code == "EUR") {
                        $products_p_temp = $products_p_temp * 1.95583;
                    }
                    break;
                case 2:
                case 3:
                    if ($dskapi_currency_code == "BGN") {
                        $products_p_temp = $products_p_temp / 1.95583;
                    }
                    break;
            }
            $products_p .= number_format($products_p_temp, 2, ".", "");
            $products_p .= '_';

            $products_name .= str_replace('"', '', str_replace("'", "", htmlspecialchars_decode($product['name'], ENT_QUOTES)));
            $products_name .= '_';
            $products_c .= strval($product['id_category_default']);
            $products_c .= '_';
            $products_m .= strval($product['id_manufacturer']);
            $products_m .= '_';
            $dskapi_image = Image::getCover($product['id_product']);
            $dskapi_link = new Link;
            $dskapi_imagePath = $dskapi_link->getImageLink($product['link_rewrite'], $dskapi_image['id_image'], 'home_default');
            if (!preg_match("~^(?:f|ht)tps?://~i", $dskapi_imagePath)) {
                $dskapi_imagePath = "https://" . $dskapi_imagePath;
            }
            $dskapi_imagePath_64 = base64_encode($dskapi_imagePath);
            $products_i .= $dskapi_imagePath_64;
            $products_i .= '_';
        }
        $products_id = trim($products_id, "_");
        $products_q = trim($products_q, "_");
        $products_p = trim($products_p, "_");
        $products_c = trim($products_c, "_");
        $products_m = trim($products_m, "_");
        $products_name = trim($products_name, "_");
        $products_i = trim($products_i, "_");

        $order = new Order((int) $this->module->currentOrder);
        if (!Validate::isLoadedObject($order)) {
            die($this->module->l('Грешка при създаване на поръчката. Моля опитайте отново.', 'validation'));
        }
        $order_id = (int) $order->id;

        $dskapi_module = Module::getInstanceByName('dskpayment');
        $dskapi_post = [
            'unicid' => $dskapi_cid,
            'first_name' => htmlspecialchars_decode($dskapi_firstname, ENT_QUOTES),
            'last_name' => htmlspecialchars_decode($dskapi_lastname, ENT_QUOTES),
            'phone' => $dskapi_phone,
            'email' => $dskapi_email,
            'address2' => str_replace('"', '', str_replace("'", "", htmlspecialchars_decode($dskapi_address2, ENT_QUOTES))),
            'address2city' => str_replace('"', '', str_replace("'", "", htmlspecialchars_decode($dskapi_address2city, ENT_QUOTES))),
            'postcode' => $dskapi_postcode,
            'price' => $dskapi_total,
            'address' => str_replace('"', '', str_replace("'", "", htmlspecialchars_decode($dskapi_address1, ENT_QUOTES))),
            'addresscity' => str_replace('"', '', str_replace("'", "", htmlspecialchars_decode($dskapi_address1city, ENT_QUOTES))),
            'products_id' => $products_id,
            'products_name' => $products_name,
            'products_q' => $products_q,
            'type_client' => $dskapi_type_client,
            'products_p' => $products_p,
            'version' => $dskapi_module->version,
            'shoporder_id' => $order_id,
            'products_c' => $products_c,
            'products_m' => $products_m,
            'products_i' => $products_i,
            'currency' => $dskapi_currency_code_send
        ];
        $dskapi_plaintext = json_encode($dskapi_post);
        $publicKeyPath = _PS_MODULE_DIR_ . 'dskpayment/keys/pub.pem';

        if (!file_exists($publicKeyPath)) {
            die($this->module->l('Грешка: Липсва публичен ключ за криптиране. Моля свържете се с администратора.', 'validation'));
        }

        $publicKeyContent = file_get_contents($publicKeyPath);
        if ($publicKeyContent === false) {
            die($this->module->l('Грешка: Не може да се прочете публичният ключ. Моля свържете се с администратора.', 'validation'));
        }

        $dskapi_publicKey = openssl_pkey_get_public($publicKeyContent);
        if ($dskapi_publicKey === false) {
            die($this->module->l('Грешка: Невалиден публичен ключ. Моля свържете се с администратора.', 'validation'));
        }

        $dskapi_a_key = openssl_pkey_get_details($dskapi_publicKey);
        if ($dskapi_a_key === false) {
            if (version_compare(PHP_VERSION, '8.0.0', '<')) {
                openssl_free_key($dskapi_publicKey);
            }
            die($this->module->l('Грешка: Не може да се получи информация за публичния ключ. Моля свържете се с администратора.', 'validation'));
        }

        $dskapi_chunkSize = ceil($dskapi_a_key['bits'] / 8) - 11;
        $dskapi_output = '';
        while ($dskapi_plaintext) {
            $dskapi_chunk = substr($dskapi_plaintext, 0, $dskapi_chunkSize);
            $dskapi_plaintext = substr($dskapi_plaintext, $dskapi_chunkSize);
            $dskapi_encrypted = '';
            if (!openssl_public_encrypt($dskapi_chunk, $dskapi_encrypted, $dskapi_publicKey)) {
                if (version_compare(PHP_VERSION, '8.0.0', '<')) {
                    openssl_free_key($dskapi_publicKey);
                }
                die($this->module->l('Грешка при криптиране на данните. Моля опитайте отново.', 'validation'));
            }
            $dskapi_output .= $dskapi_encrypted;
        }
        if (version_compare(PHP_VERSION, '8.0.0', '<')) {
            openssl_free_key($dskapi_publicKey);
        }
        $dskapi_output64 = base64_encode($dskapi_output);

        $dskapi_add_ch = curl_init();
        curl_setopt_array($dskapi_add_ch, array(
            CURLOPT_URL => DSKAPI_LIVEURL . '/function/addorders.php',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 2,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode(array('data' => $dskapi_output64)),
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "cache-control: no-cache"
            ),
        ));
        $curl_response = curl_exec($dskapi_add_ch);
        $curl_error = curl_error($dskapi_add_ch);
        $curl_http_code = curl_getinfo($dskapi_add_ch, CURLINFO_HTTP_CODE);
        curl_close($dskapi_add_ch);

        if ($curl_response === false || !empty($curl_error)) {
            // Грешка при изпълнение на curl заявката
            DskPaymentOrder::create($order_id, 0);

            Mail::Send(
                (int) (Configuration::get('PS_LANG_DEFAULT')),
                'ordersend',
                'Проблем комуникация заявка КП DSK Credit',
                [
                    '{email}' => DSKAPI_MAIL,
                    '{message}' => 'CURL Error: ' . $curl_error . "\n\n" . json_encode($dskapi_post, JSON_PRETTY_PRINT)
                ],
                DSKAPI_MAIL,
                null,
                DSKAPI_MAIL,
                strval(Configuration::get('PS_SHOP_NAME')),
                null,
                null,
                _PS_MODULE_DIR_ . 'dskpayment/mails',
                false,
                null
            );

            die($this->module->l('Има временен проблем с комуникацията към DSK Credit. Изпратен е мейл с Вашата заявка към Банката. Моля очаквайте обратна връзка от Банката за да продължите процедурата по вашата заявка за кредит.', 'validation'));
        }

        if ($curl_http_code !== 200) {
            // HTTP код различен от 200
            DskPaymentOrder::create($order_id, 0);

            Mail::Send(
                (int) (Configuration::get('PS_LANG_DEFAULT')),
                'ordersend',
                'Проблем комуникация заявка КП DSK Credit',
                [
                    '{email}' => DSKAPI_MAIL,
                    '{message}' => 'HTTP Error Code: ' . $curl_http_code . "\n\nResponse: " . $curl_response . "\n\n" . json_encode($dskapi_post, JSON_PRETTY_PRINT)
                ],
                DSKAPI_MAIL,
                null,
                DSKAPI_MAIL,
                strval(Configuration::get('PS_SHOP_NAME')),
                null,
                null,
                _PS_MODULE_DIR_ . 'dskpayment/mails',
                false,
                null
            );

            die($this->module->l('Има временен проблем с комуникацията към DSK Credit. Изпратен е мейл с Вашата заявка към Банката. Моля очаквайте обратна връзка от Банката за да продължите процедурата по вашата заявка за кредит.', 'validation'));
        }

        $paramsdskapiadd = json_decode($curl_response, true);

        if (
            (!empty($paramsdskapiadd)) &&
            isset($paramsdskapiadd['order_id']) &&
            ($paramsdskapiadd['order_id'] != 0) &&
            DskPaymentOrder::create($order_id, 0)
        ) {
            if ($dskapi_type_client == 1) {
                Tools::redirect(DSKAPI_LIVEURL . '/applicationm_step1.php?oid=' . $paramsdskapiadd['order_id'] . '&cid=' . $dskapi_cid);
            } else {
                Tools::redirect(DSKAPI_LIVEURL . '/application_step1.php?oid=' . $paramsdskapiadd['order_id'] . '&cid=' . $dskapi_cid);
            }
        } else {
            // Създаваме запис с статус 0 при неуспех
            DskPaymentOrder::create($order_id, 0);

            if (empty($paramsdskapiadd)) {
                Mail::Send(
                    (int) (Configuration::get('PS_LANG_DEFAULT')),
                    'ordersend',
                    'Проблем комуникация заявка КП DSK Credit',
                    [
                        '{email}' => DSKAPI_MAIL,
                        '{message}' => 'Empty response from API' . "\n\n" . json_encode($dskapi_post, JSON_PRETTY_PRINT)
                    ],
                    DSKAPI_MAIL,
                    null,
                    DSKAPI_MAIL,
                    strval(Configuration::get('PS_SHOP_NAME')),
                    null,
                    null,
                    _PS_MODULE_DIR_ . 'dskpayment/mails',
                    false,
                    null
                );

                die($this->module->l('Има временен проблем с комуникацията към DSK Credit. Изпратен е мейл с Вашата заявка към Банката. Моля очаквайте обратна връзка от Банката за да продължите процедурата по вашата заявка за кредит.', 'validation'));
            } else {
                die($this->module->l('Вече има създадена заявка за кредит в системата на DSK Credit с номер на Вашия ордер: ' . $order_id, 'validation'));
            }
        }
    }

    private function checkIfContextIsValid()
    {
        return true === Validate::isLoadedObject($this->context->cart)
            && true === Validate::isUnsignedInt($this->context->cart->id_customer)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_delivery)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_invoice)
            && false === $this->context->cart->isVirtualCart();
    }

    private function checkIfPaymentOptionIsAvailable()
    {
        $modules = $this->module->getPaymentModules();

        if (empty($modules)) {
            return false;
        }

        foreach ($modules as $module) {
            if (isset($module['name']) && 'dskpayment' === $module['name']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Определя дали устройството е мобилно въз основа на User-Agent
     *
     * @return int 1 за мобилно устройство, 0 за десктоп
     */
    private function detectMobileDevice(): int
    {
        $useragent = array_key_exists('HTTP_USER_AGENT', $_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : '';

        if (empty($useragent)) {
            return 0;
        }

        // Проверка за мобилни устройства чрез regex
        $mobilePattern = '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i';
        $mobilePatternShort = '/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i';

        if (preg_match($mobilePattern, $useragent) || preg_match($mobilePatternShort, substr($useragent, 0, 4))) {
            return 1;
        }

        return 0;
    }
}
