# What does it do?

This extension provides a metrics collector for your TYPO3 application and exposes an endpoint that outputs the collected metrics in a format compatible with Prometheus. This allows you to monitor application performance and behavior using Prometheus or similar monitoring tools.

## Code Example: Collecting Metrics

Here's how to inject the `CollectorService` into your own class and collect metrics:

```php
<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Service;

use AUS\MetricsExporter\Service\CollectorService;

class MyCustomService
{
    public function __construct(
        private readonly CollectorService $collectorService
    ) {
    }

    public function doSomething(): void
    {
        // Collect a gauge metric (represents a value that can go up and down)
        $gauge = $this->collectorService->getOrRegisterGauge(
            'my_extension_prefix',
            'my_custom_gauge',
            'Description of my custom gauge metric',
            ['label1', 'label2'] // Optional labels
        );
        $gauge->set(42.5, ['value1', 'value2']); // Set gauge value with label values

        // Collect a counter metric (represents a value that only increases)
        $counter = $this->collectorService->getOrRegisterCounter(
            'my_extension_prefix',
            'my_custom_counter',
            'Description of my custom counter metric',
            ['status'] // Optional labels
        );
        $counter->inc(['success']); // Increment counter by 1
        $counter->incBy(5, ['error']); // Increment counter by 5
    }
}
```

# Configuration

1. Install the extension via composer:

```bash
composer require andersundsehr/metrics_exporter
```

2. Optional: Define the data endpoint in your site configuration:

```YAML
routeEnhancers:
  PageTypeSuffix:
    type: PageType
    map:
      metrics.txt: 1717676395
```

3. Database configuration if wanted, see below.

# Visibility

Remember that metrics may contain sensitive data and should be protected from public access.

# Storage

You can change the Storage by configuring the cache "prometheus_storage" in the TYPO3 caching framework!

Cache? Isn't that cleared so my counters get lost? Yes, but you can install and properly configure "weakbit/fallback-cache" which gives you immutable caches that are not flushed by TYPO3.
