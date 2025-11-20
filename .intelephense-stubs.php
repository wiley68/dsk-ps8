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

    if (!defined('_MODULE_DIR_')) {
        define('_MODULE_DIR_', __DIR__ . '/');
    }

    if (!defined('_DB_PREFIX_')) {
        define('_DB_PREFIX_', 'ps_');
    }

    if (!defined('_MYSQL_ENGINE_')) {
        define('_MYSQL_ENGINE_', 'InnoDB');
    }

    if (class_exists(Module::class)) {
        return;
    }

    class Validate
    {
        public static function isLoadedObject($object): bool
        {
            return $object !== null;
        }

        public static function isUnsignedInt($value): bool
        {
            return is_int($value) && $value >= 0;
        }
    }

    class Image
    {
        public static function getCover(int $idProduct): array
        {
            return ['id_image' => 0];
        }
    }

    class Module
    {
        /** @var string */
        public $name = '';
        /** @var string */
        public $version = '';
        /** @var string */
        public $author = '';
        /** @var string */
        public $tab = '';
        /** @var int */
        public $need_instance = 0;
        /** @var bool */
        public $bootstrap = false;
        /** @var array */
        public $ps_versions_compliancy = [];
        /** @var string */
        public $displayName = '';
        /** @var string */
        public $description = '';
        /** @var string */
        public $confirmUninstall = '';
        /** @var string */
        public $warning = '';

        public function __construct() {}

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

        public function l(string $string, string $class = ''): string
        {
            return $string;
        }

        public function getPaymentModules(): array
        {
            return [];
        }

        public static function getInstanceByName(string $name): ?self
        {
            return new self();
        }

        public function fetch(string $template): string
        {
            return '';
        }
    }

    class PaymentModule extends Module
    {
        /** @var Context */
        public $context;
        /** @var int|null */
        public $currentOrder = null;

        public function validateOrder(
            int $cartId,
            int $orderState,
            float $amount,
            string $paymentMethod = '',
            ?string $message = null,
            array $extraVars = [],
            ?Currency $currency = null,
            bool $dontTouchAmount = false,
            $secureKey = '',
            ?Shop $shop = null
        ): void {}

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
        /** @var Context|null */
        public static $instance = null;

        /** @var Cart|null */
        public $cart = null;
        /** @var Customer|null */
        public $customer = null;
        /** @var Currency|null */
        public $currency = null;
        /** @var Language|null */
        public $language = null;
        /** @var Shop|null */
        public $shop = null;
        /** @var Controller|null */
        public $controller = null;
        /** @var Smarty|null */
        public $smarty = null;
        /** @var Link|null */
        public $link = null;

        public static function getContext(): self
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
    }

    class Smarty
    {
        /**
         * @param array<string, mixed>|string $tpl_var
         * @param mixed $value
         * @return void
         */
        public function assign($tpl_var, $value = null): void {}
    }

    class Cart
    {
        public const ONLY_PRODUCTS = 1;
        public const BOTH = 3;

        /** @var int */
        public $id;
        /** @var int|null */
        public $id_customer = null;
        /** @var int|null */
        public $id_currency = null;
        /** @var int|null */
        public $id_address_delivery = null;
        /** @var int|null */
        public $id_address_invoice = null;
        /** @var int|null */
        public function getOrderTotal(bool $withTaxes, int $type = self::BOTH): float
        {
            return 0.0;
        }

        public function isVirtualCart(): bool
        {
            return false;
        }

        /**
         * @return array<int, array<string, mixed>>
         */
        public function getProducts(bool $refresh = false): array
        {
            return [];
        }
    }

    class Customer
    {
        /** @var int */
        public $id;
        /** @var int */
        public $id_lang = 1;
        /** @var string */
        public $email = '';
        /** @var string */
        public $firstname = '';
        /** @var string */
        public $lastname = '';
        /** @var string */
        public $secure_key = '';

        public function __construct(int $id = 0)
        {
            $this->id = $id;
        }
    }

    class Address
    {
        /** @var int */
        public $id;
        /** @var int */
        public $id_customer = 0;
        /** @var string */
        public $address1 = '';
        /** @var string */
        public $address2 = '';
        /** @var string */
        public $city = '';
        /** @var string */
        public $postcode = '';
        /** @var string */
        public $phone = '';
        /** @var string */
        public $phone_mobile = '';
        /** @var string */

        public function __construct(int $id = 0)
        {
            $this->id = $id;
        }
    }

    class Product
    {
        /** @var int */
        public $id;

        public function __construct(int $id = 0)
        {
            $this->id = $id;
        }

        /**
         * @param int $idProduct
         * @param bool $usetax
         * @param int|null $idProductAttribute
         * @param int|null $decimals
         * @param int|null $divisor
         * @param bool $onlyReduc
         * @param bool $useReduc
         * @param int|null $quantity
         * @param bool $forceAssociatedTax
         * @param int|null $idCustomer
         * @param int|null $idCart
         * @param int|null $idAddress
         * @param array|null $specificPriceOutput
         * @param bool $withEcotax
         * @param bool $useGroupReduction
         * @param Context|null $context
         * @param bool $useCustomerPrice
         * @return float
         */
        public static function getPriceStatic(
            int $idProduct,
            bool $usetax = true,
            ?int $idProductAttribute = null,
            ?int $decimals = null,
            ?int $divisor = null,
            bool $onlyReduc = false,
            bool $useReduc = true,
            ?int $quantity = null,
            bool $forceAssociatedTax = false,
            ?int $idCustomer = null,
            ?int $idCart = null,
            ?int $idAddress = null,
            ?array $specificPriceOutput = null,
            bool $withEcotax = true,
            bool $useGroupReduction = true,
            ?Context $context = null,
            bool $useCustomerPrice = true
        ): float {
            return 0.0;
        }
    }

    class Currency
    {
        /** @var int */
        public $id;
        /** @var string */
        public $iso_code = 'EUR';
        /** @var int */
        public $decimals = 2;

        public function __construct(int $id = 0)
        {
            $this->id = $id;
        }
    }

    class Language
    {
        /** @var int */
        public $id;
        /** @var string */
        public $iso_code = 'en';
    }

    class Shop
    {
        /** @var int */
        public $id;
        /** @var string */
        public $name = '';

        public const CONTEXT_ALL = 1;

        public static function isFeatureActive(): bool
        {
            return false;
        }

        public static function setContext(int $context): void {}
    }

    class Controller
    {
        /** @var string */
        public $php_self = '';

        public function addCSS(string $path): void {}

        public function addJS(string $path): void {}

        public function registerJavascript(string $id, string $path, array $options = []): void {}

        public function registerStylesheet(string $id, string $path, array $options = []): void {}
    }

    class ModuleFrontController extends Controller
    {
        /** @var Context */
        public $context;
        /** @var PaymentModule|null */
        public $module;

        public function __construct()
        {
            $this->context = Context::getContext();
            $this->module = new PaymentModule();
        }
    }

    class Link
    {
        public function getModuleLink(string $module, string $controller, array $params = [], bool $ssl = false): string
        {
            return '/module/' . $module . '/' . $controller;
        }

        public function getPageLink(string $page, bool $ssl = false, int $idLang = 0, array $params = []): string
        {
            return '/' . $page;
        }

        public function getImageLink(string $rewrite, int $idImage, string $type): string
        {
            return 'https://example.com/img/' . $idImage . '-' . $type . '.jpg';
        }
    }

    class Mail
    {
        public static function Send(
            $idLang,
            $template,
            $subject,
            $templateVars,
            $to,
            $toName = '',
            $from = null,
            $fromName = null,
            $fileAttachment = [],
            $modeSMTP = null,
            $die = false,
            $idShop = null,
            $bcc = null
        ): bool {
            return true;
        }
    }

    class Db
    {
        /** @var Db|null */
        private static $instance = null;

        public static function getInstance(): self
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function execute(string $sql): bool
        {
            return true;
        }

        public function getValue($sql)
        {
            return null;
        }

        public function insert(string $table, array $data): bool
        {
            return true;
        }

        public function update(string $table, array $data, string $where): bool
        {
            return true;
        }

        public function delete(string $table, string $where): bool
        {
            return true;
        }
    }

    class DbQuery
    {
        public function select(string $fields): self
        {
            return $this;
        }

        public function from(string $table): self
        {
            return $this;
        }

        public function where(string $condition): self
        {
            return $this;
        }

        public function build(): string
        {
            return '';
        }
    }

    class ObjectModel
    {
        /** @var int */
        public $id;

        /** @var array */
        public static $definition = [];

        public const TYPE_INT = 1;
        public const TYPE_STRING = 2;
        public const TYPE_FLOAT = 3;
        public const TYPE_DATE = 4;
        public const TYPE_BOOL = 5;

        public function __construct($id = null)
        {
            if ($id) {
                $this->id = (int) $id;
            }
        }

        public function add($autoDate = true, $nullValues = false): bool
        {
            return true;
        }

        public function update($nullValues = false): bool
        {
            return true;
        }

        public function delete(): bool
        {
            return true;
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

        public static function redirect(string $url): void {}

        public static function redirectAdmin(string $url): void {}
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
    class Order
    {
        /** @var int */
        public $id;

        public function __construct(int $id = 0)
        {
            $this->id = $id;
        }
    }

    class OrderState
    {
        /** @var int */
        public $id;
        /** @var array */
        public $name = [];
        /** @var bool */
        public $send_mail = false;
        /** @var string */
        public $template = '';
        /** @var bool */
        public $invoice = false;
        /** @var string */
        public $color = '';
        /** @var bool */
        public $unremovable = false;
        /** @var bool */
        public $logable = false;

        public function add(): bool
        {
            return true;
        }
    }
}

namespace PrestaShop\PrestaShop\Core\Payment {
    class PaymentOption
    {
        public function setModuleName(string $moduleName): self
        {
            return $this;
        }

        public function setLogo(string $logoPath): self
        {
            return $this;
        }

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

namespace PrestaShopBundle\Controller\Admin {
    class FrameworkBundleAdminController
    {
        /**
         * @param string $service
         * @return mixed
         */
        public function get(string $service)
        {
            return null;
        }

        /**
         * @param string $type
         * @param string $message
         * @return void
         */
        public function addFlash(string $type, string $message): void {}

        /**
         * @param array<string> $errors
         * @return void
         */
        public function flashErrors(array $errors): void {}

        /**
         * @param string $route
         * @param array<string, mixed> $parameters
         * @return \Symfony\Component\HttpFoundation\Response
         */
        public function redirectToRoute(string $route, array $parameters = []): \Symfony\Component\HttpFoundation\Response
        {
            return new \Symfony\Component\HttpFoundation\Response();
        }

        /**
         * @param string $view
         * @param array<string, mixed> $parameters
         * @return \Symfony\Component\HttpFoundation\Response
         */
        public function render(string $view, array $parameters = []): \Symfony\Component\HttpFoundation\Response
        {
            return new \Symfony\Component\HttpFoundation\Response();
        }
    }
}

namespace Symfony\Component\HttpFoundation {
    class Request
    {
        public function __construct() {}
    }

    class Response
    {
        public function __construct() {}
    }
}

namespace PrestaShop\PrestaShop\Core\Configuration {
    interface DataConfigurationInterface
    {
        /**
         * @return array<string, mixed>
         */
        public function getConfiguration(): array;

        /**
         * @param array<string, mixed> $configuration
         * @return array<string>
         */
        public function updateConfiguration(array $configuration): array;
    }
}

namespace PrestaShop\PrestaShop\Core {
    interface ConfigurationInterface
    {
        /**
         * @param string $key
         * @param mixed $default
         * @return mixed
         */
        public function get(string $key, $default = null);

        /**
         * @param string $key
         * @param mixed $value
         * @return bool
         */
        public function set(string $key, $value): bool;
    }
}

namespace PrestaShop\PrestaShop\Core\Form {
    interface FormDataProviderInterface
    {
        /**
         * @return array<string, mixed>
         */
        public function getData(): array;

        /**
         * @param array<string, mixed> $data
         * @return array<string>
         */
        public function setData(array $data): array;
    }
}

namespace Symfony\Component\Form {
    interface FormBuilderInterface
    {
        /**
         * @param string $name
         * @param string|null $type
         * @param array<string, mixed> $options
         * @return FormBuilderInterface
         */
        public function add(string $name, ?string $type = null, array $options = []): FormBuilderInterface;
    }

    abstract class AbstractType
    {
        /**
         * @param FormBuilderInterface $builder
         * @param array<string, mixed> $options
         * @return void
         */
        public function buildForm(FormBuilderInterface $builder, array $options): void {}
    }
}

namespace Symfony\Component\Form\Extension\Core\Type {
    class TextType extends \Symfony\Component\Form\AbstractType {}
    class NumberType extends \Symfony\Component\Form\AbstractType {}
}

namespace PrestaShopBundle\Form\Admin\Type {
    class TranslatorAwareType extends \Symfony\Component\Form\AbstractType {}
    class SwitchType extends \Symfony\Component\Form\AbstractType {}
}
