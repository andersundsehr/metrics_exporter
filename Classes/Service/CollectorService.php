<?php

declare(strict_types=1);

namespace AUS\MetricsExporter\Service;

use AUS\MetricsExporter\Storage\ImmutableCachingFrameworkStorage;
use Prometheus\CollectorRegistry;
use TYPO3\CMS\Core\SingletonInterface;

class CollectorService implements SingletonInterface
{
    private readonly CollectorRegistry $registry;

    public function __construct(ImmutableCachingFrameworkStorage $cachingFrameworkStorage)
    {
        $this->registry = new CollectorRegistry($cachingFrameworkStorage);
    }

    public function getRegistry(): CollectorRegistry
    {
        return $this->registry;
    }
}
