<?php

declare(strict_types=1);

namespace PrestaShop\Module\DskPayment\Form;

use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;

/**
 * Configuration is used to save data to configuration table and retrieve from it.
 */
final class DskPaymentConfigurationDataConfiguration implements DataConfigurationInterface
{
    public const DSKAPI_STATUS = 'DSKAPI_STATUS';
    public const DSKAPI_CID = 'DSKAPI_CID';
    public const DSKAPI_REKLAMA = 'DSKAPI_REKLAMA';
    public const DSKAPI_GAP = 'DSKAPI_GAP';

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getConfiguration(): array
    {
        $return = [];

        $return['dskapi_status'] = $this->configuration->get(static::DSKAPI_STATUS);
        $return['dskapi_cid'] = $this->configuration->get(static::DSKAPI_CID);
        $return['dskapi_reklama'] = $this->configuration->get(static::DSKAPI_REKLAMA);
        $return['dskapi_gap'] = $this->configuration->get(static::DSKAPI_GAP);

        return $return;
    }

    public function updateConfiguration(array $configuration): array
    {
        $errors = [];

        if ($this->validateConfiguration($configuration)) {
            $this->configuration->set(static::DSKAPI_STATUS, $configuration['dskapi_status']);
            $this->configuration->set(static::DSKAPI_CID, $configuration['dskapi_cid']);
            $this->configuration->set(static::DSKAPI_REKLAMA, $configuration['dskapi_reklama']);
            $this->configuration->set(static::DSKAPI_GAP, $configuration['dskapi_gap']);
        }

        /* Errors are returned here. */
        return $errors;
    }

    /**
     * Ensure the parameters passed are valid.
     *
     * @return bool Returns true if no exception are thrown
     */
    public function validateConfiguration(array $configuration): bool
    {
        return
            isset($configuration['dskapi_status']) &&
            isset($configuration['dskapi_cid']) &&
            isset($configuration['dskapi_reklama']) &&
            isset($configuration['dskapi_gap']);
    }
}
