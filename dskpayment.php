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
            $this->context->controller->registerJavascript(
                'module-dskpayment-product-js',
                'modules/' . $this->name . '/js/dskpayment_product.js',
                [
                    'priority' => 200,
                    'attribute' => 'async',
                    'version' => filemtime(_PS_MODULE_DIR_ . $this->name . '/js/dskpayment_product.js')
                ]
            );
            $this->context->controller->registerStylesheet(
                'module-dskpayment-product-css',
                'modules/' . $this->name . '/css/dskpayment_product.css',
                [
                    'media' => 'all',
                    'priority' => 200,
                    'version' => filemtime(_PS_MODULE_DIR_ . $this->name . '/css/dskpayment_product.css')
                ]
            );
        }
        if ('cart' === $this->context->controller->php_self) {
            $this->context->controller->registerStylesheet(
                'module-dskpayment-cart-css',
                'modules/' . $this->name . '/css/dskpayment_cart.css',
                [
                    'media' => 'all',
                    'priority' => 200,
                    'version' => filemtime(_PS_MODULE_DIR_ . $this->name . '/css/dskpayment_cart.css')
                ]
            );
            $this->context->controller->registerJavascript(
                'module-dskpayment-cart-js',
                'modules/' . $this->name . '/js/dskpayment_cart.js',
                [
                    'priority' => 200,
                    'attribute' => 'async',
                    'version' => filemtime(_PS_MODULE_DIR_ . $this->name . '/js/dskpayment_cart.js')
                ]
            );
        }
        if ('order' === $this->context->controller->php_self) {
            $this->context->controller->registerStylesheet(
                'module-dskpayment-checkout-css',
                'modules/' . $this->name . '/css/dskpayment_checkout.css',
                [
                    'media' => 'all',
                    'priority' => 200,
                    'version' => filemtime(_PS_MODULE_DIR_ . $this->name . '/css/dskpayment_checkout.css')
                ]
            );
            $this->context->controller->registerJavascript(
                'module-dskpayment-checkout-js',
                'modules/' . $this->name . '/js/dskpayment_checkout.js',
                [
                    'priority' => 200,
                    'attribute' => 'async',
                    'version' => filemtime(_PS_MODULE_DIR_ . $this->name . '/js/dskpayment_checkout.js')
                ]
            );
        }
    }

    public function checkCurrency($cart): bool
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module))
            foreach ($currencies_module as $currency_module)
                if ($currency_order->id == $currency_module['id_currency'])
                    return true;
        return false;
    }

}
