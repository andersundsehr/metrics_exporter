<?php

declare(strict_types=1);

namespace AUS\MetricsExporter\EventListener;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

abstract class AbstractEventListener
{
    /**
     * @var array<string, mixed>
     */
    protected array $config;

    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {
        $config = $this->extensionConfiguration->get('metrics_exporter');
        assert(is_array($config));
        $this->config = $config;
    }
}
