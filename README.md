# What does it do?

This extension provides a metrics collector for your TYPO3 application and exposes an endpoint that outputs the collected metrics in a format compatible with Prometheus. This allows you to monitor application performance and behavior using Prometheus or similar monitoring tools.

It also provides a scrape proxy and PHP FPM status metrics out of the box. See extension settings.

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

# Events

## BeforeMetricsRenderEvent

Dispatched **before** the metrics are rendered. Use it to register and set additional metrics on the fly.

```php
<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\EventListener;

use AUS\MetricsExporter\Event\BeforeMetricsRenderEvent;

class MyMetricsListener
{
    public function __invoke(BeforeMetricsRenderEvent $event): void
    {
        $registry = $event->getRegistry();
        $gauge = $registry->getOrRegisterGauge('my_prefix', 'my_metric', 'My description');
        $gauge->set(42);
    }
}
```

Register the listener in your `Configuration/Services.yaml`:

```yaml
MyVendor\MyExtension\EventListener\MyMetricsListener:
  tags:
    - name: event.listener
      event: AUS\MetricsExporter\Event\BeforeMetricsRenderEvent
```

## WriteStreamEvent

Dispatched **after** the metrics are rendered, with the response stream. Use it to append extra plain-text content to the metrics output.

```php
<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\EventListener;

use AUS\MetricsExporter\Event\WriteStreamEvent;

class MyStreamListener
{
    public function __invoke(WriteStreamEvent $event): void
    {
        $event->write('# Extra info appended to the metrics output');
    }
}
```

Register the listener in your `Configuration/Services.yaml`:

```yaml
MyVendor\MyExtension\EventListener\MyStreamListener:
  tags:
    - name: event.listener
      event: AUS\MetricsExporter\Event\WriteStreamEvent
```

# Visibility

Remember that metrics may contain sensitive data and should be protected from public access.

# Storage

You can change the Storage by configuring the cache "prometheus_storage" in the TYPO3 caching framework!

Cache? Isn't that cleared so my counters get lost? Yes, but you can install and properly configure "weakbit/fallback-cache" which gives you immutable caches that are not flushed by TYPO3.
