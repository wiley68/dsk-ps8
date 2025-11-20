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

/**
 * Main module controller that integrates the DSK payment option with PrestaShop.
 */
class DskPayment extends PaymentModule
{

    const HOOKS = [
        'ActionFrontControllerSetMedia',
        'displayProductAdditionalInfo',
        'displayShoppingCart',
        'paymentOptions'
    ];

    /**
     * Initializes module metadata and default configuration.
     */
    public function __construct()
    {
        $this->name = 'dskpayment';
        $this->tab = 'payments_gateways';
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

    /**
     * Handles module installation, hooks registration and order state creation.
     *
     * @return bool
     */
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

    /**
     * Removes module configuration on uninstall.
     *
     * @return bool
     */
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

    /**
     * Enables the new translation system introduced in PrestaShop 1.7+.
     *
     * @return bool
     */
    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    /**
     * Redirects back-office configuration to the Symfony controller.
     *
     * @return void
     */
    public function getContent(): void
    {
        $route = $this->get('router')->generate('dskpayment_configuration_form');
        Tools::redirectAdmin($route);
    }

    /**
     * Registers CSS/JS assets depending on the current front controller.
     *
     * @param array $params
     *
     * @return void
     */
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

    /**
     * Renders product additional info block with the DSK financing widget.
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayProductAdditionalInfo($params): string
    {
        if ('product' !== $this->context->controller->php_self) {
            return '';
        }

        $dskapi_product_id = (int) Tools::getValue('id_product');
        if ($dskapi_product_id <= 0) {
            return '';
        }

        $dskapi_price = (float) Product::getPriceStatic($dskapi_product_id, true);
        if ($dskapi_price <= 0) {
            return '';
        }

        return $this->renderDskWidget(
            $dskapi_price,
            $dskapi_product_id,
            'module:dskpayment/views/templates/hook/dskpayment_product.tpl'
        );

    }

    /**
     * Renders the DSK widget on the shopping cart page.
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayShoppingCart($params): string
    {
        if ('cart' !== $this->context->controller->php_self) {
            return '';
        }

        $dskapi_price = (float) $this->context->cart->getOrderTotal(true);
        if ($dskapi_price <= 0 || !$this->hasAvailableProductInCart()) {
            return '';
        }

        $dskapi_product_id = $this->resolveCartProductId();

        return $this->renderDskWidget(
            $dskapi_price,
            $dskapi_product_id,
            'module:dskpayment/views/templates/hook/dskpayment_cart.tpl'
        );
    }

    /**
     * Builds the data needed for rendering the financing widget and returns the rendered template.
     *
     * @param float $price
     * @param int $productId
     * @param string $templatePath
     *
     * @return string
     */
    private function renderDskWidget(float $price, int $productId, string $templatePath): string
    {
        $dskapi_status = (int) Configuration::get('DSKAPI_STATUS');
        $dskapi_currency_code = $this->context->currency->iso_code ?? '';
        $dskapi_gap = (int) Configuration::get('DSKAPI_GAP');

        if ($dskapi_status === 0 || !in_array($dskapi_currency_code, ['EUR', 'BGN'], true)) {
            return '';
        }

        $dskapi_cid = (string) Configuration::get('DSKAPI_CID');
        if (empty($dskapi_cid)) {
            return '';
        }

        $dskapi_price = $price;
        $dskapi_sign = 'лв.';
        $response = $this->makeApiRequest('/function/geteur.php?cid=' . urlencode($dskapi_cid));
        if ($response === null) {
            return '';
        }

        $dskapi_eur = (int) ($response['dsk_eur'] ?? 0);
        switch ($dskapi_eur) {
            case 1:
                $dskapi_sign = 'лв.';
                if ($dskapi_currency_code === 'EUR') {
                    $dskapi_price = (float) number_format($dskapi_price * 1.95583, 2, '.', '');
                }
                break;
            case 2:
                $dskapi_sign = 'евро';
                if ($dskapi_currency_code === 'BGN') {
                    $dskapi_price = (float) number_format($dskapi_price / 1.95583, 2, '.', '');
                }
                break;
        }

        $apiUrl = '/function/getproduct.php?cid=' . urlencode($dskapi_cid)
            . '&price=' . urlencode((string) $dskapi_price)
            . '&product_id=' . urlencode((string) $productId);
        $paramsdskapi = $this->makeApiRequest($apiUrl);
        if ($paramsdskapi === null) {
            return '';
        }

        if (
            !isset(
            $paramsdskapi['dsk_options'],
            $paramsdskapi['dsk_is_visible'],
            $paramsdskapi['dsk_status'],
            $paramsdskapi['dsk_button_status'],
            $paramsdskapi['dsk_reklama']
        )
        ) {
            return '';
        }

        $dskapi_options = (bool) $paramsdskapi['dsk_options'];
        $dskapi_is_visible = (bool) $paramsdskapi['dsk_is_visible'];
        $dskapi_button_status = (int) $paramsdskapi['dsk_button_status'];

        if (
            $dskapi_price <= 0 ||
            !$dskapi_options ||
            !$dskapi_is_visible ||
            (int) $paramsdskapi['dsk_status'] !== 1 ||
            $dskapi_button_status === 0
        ) {
            return '';
        }

        $dskapi_vnoski_visible = (int) ($paramsdskapi['dsk_vnoski_visible'] ?? 0);
        $defaultVnoski = (int) ($paramsdskapi['dsk_vnoski_default'] ?? 0);
        $dskapi_vnoski_visible_arr = [];
        for ($vnoska = 3; $vnoska <= 48; $vnoska++) {
            $bitPosition = $vnoska - 3;
            $bitMask = 1 << $bitPosition;
            $dskapi_vnoski_visible_arr[$vnoska] = ($dskapi_vnoski_visible & $bitMask) !== 0 || $defaultVnoski === $vnoska;
        }

        $dskapi_is_mobile = $this->isMobileDevice();
        $prefix = $dskapi_is_mobile ? 'dskapim' : 'dskapi';
        $imgPrefix = $dskapi_is_mobile ? 'dskm' : 'dsk';

        $this->context->smarty->assign([
            'dskapi_zaglavie' => $paramsdskapi['dsk_zaglavie'] ?? '',
            'dskapi_custom_button_status' => (int) ($paramsdskapi['dsk_custom_button_status'] ?? 0),
            'dskapi_button_normal_custom' => DSKAPI_LIVEURL . '/calculators/assets/img/custom_buttons/' . urlencode($dskapi_cid) . '.png',
            'dskapi_button_hover_custom' => DSKAPI_LIVEURL . '/calculators/assets/img/custom_buttons/' . urlencode($dskapi_cid) . '_hover.png',
            'dskapi_button_normal' => DSKAPI_LIVEURL . '/calculators/assets/img/buttons/dsk.png',
            'dskapi_button_hover' => DSKAPI_LIVEURL . '/calculators/assets/img/buttons/dsk-hover.png',
            'dskapi_isvnoska' => (int) ($paramsdskapi['dsk_isvnoska'] ?? 0),
            'dskapi_vnoski' => $defaultVnoski,
            'dskapi_vnoska' => number_format((float) ($paramsdskapi['dsk_vnoska'] ?? 0), 2, '.', ''),
            'dskapi_price' => number_format($dskapi_price, 2, '.', ''),
            'dskapi_cid' => $dskapi_cid,
            'dskapi_product_id' => $productId,
            'DSKAPI_LIVEURL' => DSKAPI_LIVEURL,
            'dskapi_button_status' => $dskapi_button_status,
            'dskapi_maxstojnost' => (float) number_format((float) ($paramsdskapi['dsk_maxstojnost'] ?? 0), 2, '.', ''),
            'dskapi_minstojnost' => (float) number_format((float) ($paramsdskapi['dsk_minstojnost'] ?? 0), 2, '.', ''),
            'dskapi_PopUp_Detailed_v1' => $prefix . '_PopUp_Detailed_v1',
            'dskapi_Mask' => $prefix . '_Mask',
            'dskapi_picture' => DSKAPI_LIVEURL . '/calculators/assets/img/' . $imgPrefix . ($paramsdskapi['dsk_reklama'] ?? 0) . '.png',
            'dskapi_product_name' => $prefix . '_product_name',
            'dskapi_body_panel_txt3' => $prefix . '_body_panel_txt3',
            'dskapi_body_panel_txt4' => $prefix . '_body_panel_txt4',
            'dskapi_body_panel_txt3_left' => $prefix . '_body_panel_txt3_left',
            'dskapi_body_panel_txt3_right' => $prefix . '_body_panel_txt3_right',
            'dskapi_sumi_panel' => $prefix . '_sumi_panel',
            'dskapi_kredit_panel' => $prefix . '_kredit_panel',
            'dskapi_body_panel_footer' => $prefix . '_body_panel_footer',
            'dskapi_body_panel_left' => $prefix . '_body_panel_left',
            'dskapi_vnoski_visible_arr' => $dskapi_vnoski_visible_arr,
            'DSKAPI_VERSION' => $this->version,
            'dskapi_sign' => $dskapi_sign,
            'dskapi_currency_code' => $dskapi_currency_code,
            'dskapi_eur' => $dskapi_eur,
            'dskapi_gap' => $dskapi_gap
        ]);

        return $this->fetch($templatePath);
    }

    /**
     * Provides available payment options shown during checkout.
     *
     * @param array $params
     *
     * @return array
     */
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
        curl_setopt($dskapi_ch, CURLOPT_MAXREDIRS, 2);
        curl_setopt($dskapi_ch, CURLOPT_TIMEOUT, 5);
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

        $dskapi_eur = (int) $paramsdskapieur['dsk_eur'];
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

    /**
     * Checks whether the current cart contains at least one available product.
     *
     * @return bool
     */
    private function hasAvailableProductInCart(): bool
    {
        if (!$this->context->cart instanceof Cart) {
            return false;
        }

        $products = $this->context->cart->getProducts(true);

        if (empty($products)) {
            return false;
        }

        foreach ($products as $product) {
            $quantity = (int) ($product['quantity'] ?? 0);
            $availableForOrder = (bool) ($product['available_for_order'] ?? true);

            if ($quantity > 0 && $availableForOrder) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines the product identifier to send to the API when building the cart widget.
     * If the cart contains exactly one unique product, its ID is returned. Otherwise 0 is used.
     *
     * @return int
     */
    private function resolveCartProductId(): int
    {
        if (!$this->context->cart instanceof Cart) {
            return 0;
        }

        $products = $this->context->cart->getProducts(true);
        if (empty($products)) {
            return 0;
        }

        $uniqueIds = [];
        foreach ($products as $product) {
            $productId = (int) ($product['id_product'] ?? 0);
            if ($productId > 0) {
                $uniqueIds[$productId] = true;
            }
            if (count($uniqueIds) > 1) {
                return 0;
            }
        }

        reset($uniqueIds);
        $firstKey = key($uniqueIds);

        return (int) ($firstKey ?? 0);
    }

    /**
     * Извършва API заявка и връща декодирания JSON отговор
     *
     * @param string $endpoint API endpoint (без базовия URL)
     * @param int $timeout Timeout в секунди
     * @return array|null Декодираният JSON отговор или null при грешка
     */
    /**
     * Executes an HTTP request to the DSK API and returns the decoded response.
     *
     * @param string $endpoint Relative API endpoint path
     * @param int $timeout Request timeout in seconds
     *
     * @return array|null
     */
    private function makeApiRequest(string $endpoint, int $timeout = 5): ?array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_URL, DSKAPI_LIVEURL . $endpoint);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200 || !empty($curlError)) {
            return null;
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    /**
     * Проверява дали устройството е мобилно
     *
     * @return bool
     */
    /**
     * Detects whether the current visitor uses a mobile device.
     *
     * @return bool
     */
    private function isMobileDevice(): bool
    {
        $useragent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (empty($useragent)) {
            return false;
        }

        $mobilePattern = '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i';

        return (bool) preg_match($mobilePattern, $useragent)
            || (bool) preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4));
    }
}
