<?php

/**
 * @File: dskpayment.php
 * @Author: Ilko Ivanov
 * @Author e-mail: ilko.iv@gmail.com
 * @Publisher: Avalon Ltd
 * @Publisher e-mail: home@avalonbg.com
 * @Owner: Банка ДСК
 * @Version: 1.2.0
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

defined('DSKAPI_LIVEURL') or define('DSKAPI_LIVEURL', 'https://dsk.avalon-bg.eu');
defined('DSKAPI_MAIL') or define('DSKAPI_MAIL', 'home@avalonbg.com');

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class DskPayment extends PaymentModule
{

    const HOOKS = [
        'ActionFrontControllerSetMedia',
        'displayProductAdditionalInfo',
        'displayShoppingCart',
        'paymentOptions'
    ];

    public function __construct()
    {
        $this->name = 'dskpayment';
        $this->version = '1.2.0';
        $this->author = 'Ilko Ivanov';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => '8.99.99'];

        parent::__construct();

        $this->displayName = 'DSK Credit API покупки на Кредит';
        $this->description = 'Дава възможност на Вашите клиенти да закупуват стока на изплащане с DSK Credit API.';
        $this->confirmUninstall = 'Сигурни ли сте, че желаете да го деинсталирате?';
        if (!Configuration::get('DSKPAYMENT_NAME')) {
            $this->warning = 'Няма предоставено име';
        }
    }

    public function install(): bool
    {
        if (Shop::isFeatureActive())
            Shop::setContext(Shop::CONTEXT_ALL);

        if (!(Configuration::get('PS_OS_DSKPAYMENT') > 0)) {
            $dskpayment_OrderState = new OrderState();
            $dskpayment_OrderState->name = array_fill(0, 10, "DSK Credit API покупки на Кредит");
            $dskpayment_OrderState->send_mail = false;
            $dskpayment_OrderState->template = "";
            $dskpayment_OrderState->invoice = false;
            $dskpayment_OrderState->color = "#DDEAF8";
            $dskpayment_OrderState->unremovable = false;
            $dskpayment_OrderState->logable = false;
            $dskpayment_OrderState->add();
            Configuration::updateValue('PS_OS_DSKPAYMENT', $dskpayment_OrderState->id);
        }

        return parent::install() &&
            (bool) $this->registerHook(static::HOOKS) &&
            Configuration::updateValue(
                'DSKPAYMENT_NAME',
                'DSK Credit API покупки на Кредит'
            );
    }

    public function uninstall(): bool
    {
        if (
            !parent::uninstall() ||
            !Configuration::deleteByName('DSKPAYMENT_NAME') ||
            !Configuration::deleteByName('PS_OS_DSKPAYMENT') ||
            !Configuration::deleteByName('dskapi_status') ||
            !Configuration::deleteByName('dskapi_cid') ||
            !Configuration::deleteByName('dskapi_reklama') ||
            !Configuration::deleteByName('dskapi_gap')
        )
            return false;
        return true;
    }

    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    public function getContent(): void
    {
        $route = $this->get('router')->generate('dskpayment_configuration_form');
        Tools::redirectAdmin($route);
    }

    public function hookActionFrontControllerSetMedia($params): void
    {
        if ('product' === $this->context->controller->php_self) {
            $productJsPath = _PS_MODULE_DIR_ . $this->name . '/js/dskpayment_product.js';
            $productCssPath = _PS_MODULE_DIR_ . $this->name . '/css/dskpayment_product.css';

            if (file_exists($productJsPath)) {
                $this->context->controller->registerJavascript(
                    'module-dskpayment-product-js',
                    'modules/' . $this->name . '/js/dskpayment_product.js',
                    [
                        'priority' => 200,
                        'attribute' => 'async',
                        'version' => filemtime($productJsPath)
                    ]
                );
            }
            if (file_exists($productCssPath)) {
                $this->context->controller->registerStylesheet(
                    'module-dskpayment-product-css',
                    'modules/' . $this->name . '/css/dskpayment_product.css',
                    [
                        'media' => 'all',
                        'priority' => 200,
                        'version' => filemtime($productCssPath)
                    ]
                );
            }
        }
        if ('cart' === $this->context->controller->php_self) {
            $cartJsPath = _PS_MODULE_DIR_ . $this->name . '/js/dskpayment_cart.js';
            $cartCssPath = _PS_MODULE_DIR_ . $this->name . '/css/dskpayment_cart.css';

            if (file_exists($cartCssPath)) {
                $this->context->controller->registerStylesheet(
                    'module-dskpayment-cart-css',
                    'modules/' . $this->name . '/css/dskpayment_cart.css',
                    [
                        'media' => 'all',
                        'priority' => 200,
                        'version' => filemtime($cartCssPath)
                    ]
                );
            }
            if (file_exists($cartJsPath)) {
                $this->context->controller->registerJavascript(
                    'module-dskpayment-cart-js',
                    'modules/' . $this->name . '/js/dskpayment_cart.js',
                    [
                        'priority' => 200,
                        'attribute' => 'async',
                        'version' => filemtime($cartJsPath)
                    ]
                );
            }
        }
        if ('order' === $this->context->controller->php_self) {
            $checkoutJsPath = _PS_MODULE_DIR_ . $this->name . '/js/dskpayment_checkout.js';
            $checkoutCssPath = _PS_MODULE_DIR_ . $this->name . '/css/dskpayment_checkout.css';

            if (file_exists($checkoutCssPath)) {
                $this->context->controller->registerStylesheet(
                    'module-dskpayment-checkout-css',
                    'modules/' . $this->name . '/css/dskpayment_checkout.css',
                    [
                        'media' => 'all',
                        'priority' => 200,
                        'version' => filemtime($checkoutCssPath)
                    ]
                );
            }
            if (file_exists($checkoutJsPath)) {
                $this->context->controller->registerJavascript(
                    'module-dskpayment-checkout-js',
                    'modules/' . $this->name . '/js/dskpayment_checkout.js',
                    [
                        'priority' => 200,
                        'attribute' => 'async',
                        'version' => filemtime($checkoutJsPath)
                    ]
                );
            }
        }
    }

    public function hookDisplayProductAdditionalInfo($params): void {}

    public function hookDisplayShoppingCart($params): void {}

    public function hookPaymentOptions($params)
    {
        if (empty($params['cart'])) {
            return [];
        }

        $cart = $params['cart'];

        if ($cart->isVirtualCart()) {
            return [];
        }

        $dskapi_cid = (string) Configuration::get('dskapi_cid');
        $dskapi_price = (float) $cart->getOrderTotal(true);

        $dskapi_ch = curl_init();
        curl_setopt($dskapi_ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($dskapi_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($dskapi_ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($dskapi_ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($dskapi_ch, CURLOPT_URL, DSKAPI_LIVEURL . '/function/getminmax.php?cid=' . $dskapi_cid);

        $response = curl_exec($dskapi_ch);
        $httpCode = curl_getinfo($dskapi_ch, CURLINFO_HTTP_CODE);
        curl_close($dskapi_ch);
        if ($response === false || $httpCode !== 200) {
            return [];
        }
        $paramsdskapi = json_decode($response, true);
        if ($paramsdskapi === null) {
            return [];
        }

        $dskapi_minstojnost = (float) $paramsdskapi['dsk_minstojnost'];
        $dskapi_maxstojnost = (float) $paramsdskapi['dsk_maxstojnost'];
        $dskapi_min_000 = (float) $paramsdskapi['dsk_min_000'];
        $dskapi_status_cp = (string) $paramsdskapi['dsk_status'];

        $dskapi_purcent = (float) $paramsdskapi['dsk_purcent'];
        $dskapi_vnoski_default = (int) $paramsdskapi['dsk_vnoski_default'];
        if (($dskapi_purcent === 0) && ($dskapi_vnoski_default <= 6)) {
            $dskapi_minstojnost = $dskapi_min_000;
        }

        if (!$this->context->currency || !$this->context->currency->iso_code) {
            return [];
        }

        $dskapi_eur = 0;
        $dskapi_currency_code = $this->context->currency->iso_code;

        $dskapi_ch_eur = curl_init();
        curl_setopt($dskapi_ch_eur, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($dskapi_ch_eur, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($dskapi_ch_eur, CURLOPT_MAXREDIRS, 3);
        curl_setopt($dskapi_ch_eur, CURLOPT_TIMEOUT, 5);
        curl_setopt($dskapi_ch_eur, CURLOPT_URL, DSKAPI_LIVEURL . '/function/geteur.php?cid=' . $dskapi_cid);

        $response = curl_exec($dskapi_ch_eur);
        $httpCode = curl_getinfo($dskapi_ch_eur, CURLINFO_HTTP_CODE);
        curl_close($dskapi_ch_eur);
        if ($response === false || $httpCode !== 200) {
            return [];
        }
        $paramsdskapieur = json_decode($response, true);
        if ($paramsdskapieur === null) {
            return [];
        }

        $dskapi_eur = (int)$paramsdskapieur['dsk_eur'];
        switch ($dskapi_eur) {
            case 0:
                break;
            case 1:
                if ($dskapi_currency_code == "EUR") {
                    $dskapi_price = (float) number_format($dskapi_price * 1.95583, 2, ".", "");
                }
                break;
            case 2:
                if ($dskapi_currency_code == "BGN") {
                    $dskapi_price = (float) number_format($dskapi_price / 1.95583, 2, ".", "");
                }
                break;
        }

        if (
            $dskapi_status_cp == 0 ||
            $dskapi_price < $dskapi_minstojnost ||
            $dskapi_price > $dskapi_maxstojnost
        ) {
            return [];
        }

        $this->context->smarty->assign([
            'dskapi_logo' => _MODULE_DIR_ . $this->name . '/logo.png'
        ]);

        $payment_options = [];

        $newOption_DSK = new PaymentOption();
        $newOption_DSK->setModuleName($this->name);
        $newOption_DSK->setCallToActionText('DSK Credit API покупки на Кредит');
        $newOption_DSK->setAdditionalInformation($this->fetch('module:dskpayment/views/templates/hook/dskpayment_checkout.tpl'));
        $payment_options[] = $newOption_DSK;

        return $payment_options;
    }
}
