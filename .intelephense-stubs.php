<?php

/**
 * Стъб файл за Intelephense когато модулът се отваря извън пълната PrestaShop инсталация.
 * Тук се дефинират минимални версии на класове/методи, които често се използват.
 * Забележка: Файлът не трябва да се включва в продукция.
 */

declare(strict_types=1);

namespace {

    if (!defined('_PS_MODULE_DIR_')) {
        define('_PS_MODULE_DIR_', __DIR__ . '/');
    }

    if (class_exists(Module::class)) {
        return;
    }

    class Module
    {
        public string $name = '';
        public string $version = '';
        public string $author = '';
        public string $tab = '';
        public int $need_instance = 0;
        public bool $bootstrap = false;
        public array $ps_versions_compliancy = [];
        public string $displayName = '';
        public string $description = '';
        public string $confirmUninstall = '';
        public string $warning = '';

        public function __construct()
        {
        }

        public function install(): bool
        {
            return true;
        }

        public function uninstall(): bool
        {
            return true;
        }

        public function registerHook($hookName): bool
        {
            return true;
        }

        public function get(string $service)
        {
            return new class {
                public function generate(string $route): string
                {
                    return '/' . $route;
                }
            };
        }

        public function getCurrency(int $idCurrency)
        {
            return [
                [
                    'id_currency' => $idCurrency,
                ],
            ];
        }

        public function l(string $string): string
        {
            return $string;
        }
    }

    class PaymentModule extends Module
    {
        public Context $context;

        public function validateOrder(
            int $cartId,
            int $orderState,
            float $amount,
            string $paymentMethod = '',
            string $message = '',
            array $extraVars = [],
            ?Currency $currency = null,
            bool $dontTouchAmount = false,
            bool $secureKey = false,
            ?Shop $shop = null
        ): void {
        }

        /**
         * @return PrestaShop\PrestaShop\Core\Payment\PaymentOption[]
         */
        public function getPaymentOptions(?Currency $currency = null): array
        {
            return [];
        }
    }

    class Context
    {
        public static ?Context $instance = null;

        public ?Cart $cart = null;
        public ?Customer $customer = null;
        public ?Currency $currency = null;
        public ?Language $language = null;
        public ?Shop $shop = null;
        public ?Controller $controller = null;

        public static function getContext(): self
        {
            return self::$instance ??= new self();
        }
    }

    class Cart
    {
        public const ONLY_PRODUCTS = 1;
        public const BOTH = 3;

        public int $id;
        public ?int $id_customer = null;
        public ?int $id_currency = null;

        public function getOrderTotal(bool $withTaxes, int $type = self::BOTH): float
        {
            return 0.0;
        }
    }

    class Customer
    {
        public int $id;
        public string $email = '';
        public string $firstname = '';
        public string $lastname = '';
    }

    class Currency
    {
        public int $id;
        public string $iso_code = 'EUR';
        public int $decimals = 2;

        public function __construct(int $id = 0)
        {
            $this->id = $id;
        }
    }

    class Language
    {
        public int $id;
        public string $iso_code = 'en';
    }

    class Shop
    {
        public int $id;
        public string $name = '';

        public const CONTEXT_ALL = 1;

        public static function isFeatureActive(): bool
        {
            return false;
        }

        public static function setContext(int $context): void
        {
        }
    }

    class Controller
    {
        public string $php_self = '';

        public function addCSS(string $path): void
        {
        }

        public function addJS(string $path): void
        {
        }

        public function registerJavascript(string $id, string $path, array $options = []): void
        {
        }

        public function registerStylesheet(string $id, string $path, array $options = []): void
        {
        }
    }

    class Db
    {
        private static ?Db $instance = null;

        public static function getInstance(): self
        {
            return self::$instance ??= new self();
        }

        public function execute(string $sql): bool
        {
            return true;
        }

        public function getValue(string $sql)
        {
            return null;
        }
    }

    class Tools
    {
        public static function getValue(string $key, $default = null)
        {
            return $default;
        }

        public static function isSubmit(string $key): bool
        {
            return false;
        }

        public static function redirect(string $url): void
        {
        }

        public static function redirectAdmin(string $url): void
        {
        }
    }

    if (!function_exists('pSQL')) {
        function pSQL(string $string, bool $htmlOK = false): string
        {
            return $string;
        }
    }

    class Configuration
    {
        public static function get(string $key, $default = null)
        {
            return $default;
        }

        public static function updateValue(string $key, $value): bool
        {
            return true;
        }

        public static function deleteByName(string $key): bool
        {
            return true;
        }
    }
    class OrderState
    {
        public int $id;
        public array $name = [];
        public bool $send_mail = false;
        public string $template = '';
        public bool $invoice = false;
        public string $color = '';
        public bool $unremovable = false;
        public bool $logable = false;

        public function add(): bool
        {
            return true;
        }
    }

}

namespace PrestaShop\PrestaShop\Core\Payment {
    class PaymentOption
    {
        public function setCallToActionText(string $text): self
        {
            return $this;
        }

        public function setAction(string $action): self
        {
            return $this;
        }

        public function setAdditionalInformation(string $info): self
        {
            return $this;
        }

        public function setInputs(array $inputs): self
        {
            return $this;
        }
    }
}

