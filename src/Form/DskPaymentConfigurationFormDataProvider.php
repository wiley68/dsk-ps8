<?php

declare(strict_types=1);

namespace PrestaShop\Module\DskPayment\Form;

use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;

/**
 * Provider is responsible for providing form data, in this case, it is returned from the configuration component.
 *
 * Class DskPaymentConfigurationFormDataProvider
 */
class DskPaymentConfigurationFormDataProvider implements FormDataProviderInterface
{
    /**
     * @var DataConfigurationInterface
     */
    private $dskPaymentConfigurationDataConfiguration;

    public function __construct(DataConfigurationInterface $dskPaymentConfigurationDataConfiguration)
    {
        $this->dskPaymentConfigurationDataConfiguration = $dskPaymentConfigurationDataConfiguration;
    }

    public function getData(): array
    {
        return $this->dskPaymentConfigurationDataConfiguration->getConfiguration();
    }

    public function setData(array $data): array
    {
        return $this->dskPaymentConfigurationDataConfiguration->updateConfiguration($data);
    }
}
