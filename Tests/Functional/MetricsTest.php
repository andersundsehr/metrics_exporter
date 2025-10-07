<?php

declare(strict_types=1);

namespace AUS\MetricsExporter\Tests\Functional;

use AUS\MetricsExporter\Service\CollectorService;
use PHPUnit\Framework\Attributes\Test;
use Prometheus\Exception\MetricsRegistrationException;
use Symfony\Component\Filesystem\Filesystem;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class MetricsTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'andersundsehr/metrics_exporter'
    ];

    private CollectorService $collectorService;

    protected function setUp(): void
    {
        $GLOBALS['EXEC_TIME'] = 1740476618;
        putenv('typo3DatabaseDriver=pdo_sqlite');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->copySiteConfiguration();

        // Initialize the collector service
        $this->collectorService = GeneralUtility::makeInstance(CollectorService::class);

        // Make sure the registry is clean for each test
        $this->collectorService->getRegistry()->wipeStorage();
    }

    private function copySiteConfiguration(): void
    {
        $sourcePath = __DIR__ . '/../Fixtures/Sites/';
        // there the SiteConfiguration::getAllSiteConfigurationFromFiles it looks for our sites if it changes, check the path there
        $destinationPath = $this->instancePath . '/typo3conf/sites/default/';

        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0777, true);
        }

        (new Filesystem())->copy(
            $sourcePath . 'config.yaml',
            $destinationPath . 'config.yaml',
            true
        );
    }

    /**
     * @throws MetricsRegistrationException
     */
    #[Test]
    public function testMetricsExportReturnsPrometheusFormattedGauge(): void
    {
        $this->setUpFrontendRootPage(1);

        $gauge = $this->collectorService->getRegistry()->getOrRegisterGauge('tx_metricsexporter', 'test', 'Checks if the exporter is working', ['label1', 'label2']);
        $gauge->set(123.0, ['value1', 'value2']);

        $request = (new InternalRequest());
        $request = $request->withMethod('GET')->withQueryParameter('type', '1717676395');

        $response = $this->executeFrontendSubRequest($request);

        // Verify the response was successful
        self::assertEquals(200, $response->getStatusCode());

        $responseBody = (string)$response->getBody();
        self::assertStringContainsString(<<<TEXT
# HELP tx_metricsexporter_test Checks if the exporter is working
# TYPE tx_metricsexporter_test gauge
tx_metricsexporter_test{label1="value1",label2="value2"} 123
TEXT
, $responseBody);
    }

    /**
     * @throws MetricsRegistrationException
     */
    #[Test]
    public function testMetricsExportReturnsPrometheusFormattedCounter(): void
    {
        $this->setUpFrontendRootPage(1);

        // Register and increment a counter
        $counter = $this->collectorService->getRegistry()->getOrRegisterCounter(
            'tx_metricsexporter',
            'requests_total',
            'Total number of HTTP requests',
            ['method', 'endpoint']
        );
        $counter->incBy(5, ['GET', '/api/users']);
        $counter->inc(['POST', '/api/users']);

        $request = (new InternalRequest());
        $request = $request->withMethod('GET')->withQueryParameter('type', '1717676395');

        $response = $this->executeFrontendSubRequest($request);

        // Verify the response was successful
        self::assertEquals(200, $response->getStatusCode());

        $responseBody = (string)$response->getBody();
        self::assertStringContainsString(<<<TEXT
# HELP tx_metricsexporter_requests_total Total number of HTTP requests
# TYPE tx_metricsexporter_requests_total counter
TEXT
, $responseBody);
        self::assertStringContainsString('tx_metricsexporter_requests_total{method="GET",endpoint="/api/users"} 5', $responseBody);
        self::assertStringContainsString('tx_metricsexporter_requests_total{method="POST",endpoint="/api/users"} 1', $responseBody);
    }

    /**
     * @throws MetricsRegistrationException
     */
    #[Test]
    public function testMetricsExportReturnsPrometheusFormattedHistogram(): void
    {
        $this->setUpFrontendRootPage(1);

        // Register and observe a histogram
        $histogram = $this->collectorService->getRegistry()->getOrRegisterHistogram(
            'tx_metricsexporter',
            'response_time_seconds',
            'Response time in seconds',
            ['endpoint'],
            [0.1, 0.25, 0.5, 1, 2.5, 5, 10]
        );
        $histogram->observe(0.15, ['/api/users']);
        $histogram->observe(0.75, ['/api/users']);
        $histogram->observe(3.5, ['/api/users']);

        $request = (new InternalRequest());
        $request = $request->withMethod('GET')->withQueryParameter('type', '1717676395');

        $response = $this->executeFrontendSubRequest($request);

        // Verify the response was successful
        self::assertEquals(200, $response->getStatusCode());

        $responseBody = (string)$response->getBody();
        self::assertStringContainsString(<<<TEXT
# HELP tx_metricsexporter_response_time_seconds Response time in seconds
# TYPE tx_metricsexporter_response_time_seconds histogram
TEXT
, $responseBody);

        // Test for buckets, count and sum
        self::assertStringContainsString('tx_metricsexporter_response_time_seconds_bucket{endpoint="/api/users",le="0.1"} 0', $responseBody);
        self::assertStringContainsString('tx_metricsexporter_response_time_seconds_bucket{endpoint="/api/users",le="0.25"} 1', $responseBody);
        self::assertStringContainsString('tx_metricsexporter_response_time_seconds_bucket{endpoint="/api/users",le="0.5"} 1', $responseBody);
        self::assertStringContainsString('tx_metricsexporter_response_time_seconds_bucket{endpoint="/api/users",le="1"} 2', $responseBody);
        self::assertStringContainsString('tx_metricsexporter_response_time_seconds_count{endpoint="/api/users"} 3', $responseBody);
        self::assertStringContainsString('tx_metricsexporter_response_time_seconds_sum{endpoint="/api/users"} 4.4', $responseBody);
    }

    /**
     * @throws MetricsRegistrationException
     */
    #[Test]
    public function testMetricsExportReturnsPrometheusFormattedSummary(): void
    {
        $this->setUpFrontendRootPage(1);

        // Register and observe a summary
        $summary = $this->collectorService->getRegistry()->getOrRegisterSummary(
            'tx_metricsexporter',
            'request_size_bytes',
            'Request size in bytes',
            ['method'],
            86400,
            [0.01, 0.05, 0.5, 0.95, 0.99]
        );
        $summary->observe(100, ['GET']);
        $summary->observe(250, ['GET']);
        $summary->observe(500, ['GET']);
        $summary->observe(1000, ['GET']);

        $request = (new InternalRequest());
        $request = $request->withMethod('GET')->withQueryParameter('type', '1717676395');

        $response = $this->executeFrontendSubRequest($request);

        // Verify the response was successful
        self::assertEquals(200, $response->getStatusCode());

        $responseBody = (string)$response->getBody();
        self::assertStringContainsString(<<<TEXT
# HELP tx_metricsexporter_request_size_bytes Request size in bytes
# TYPE tx_metricsexporter_request_size_bytes summary
TEXT
, $responseBody);

        // Test for quantiles, count and sum
        self::assertStringContainsString('tx_metricsexporter_request_size_bytes{method="GET",quantile="0.01"} 100', $responseBody);
        self::assertStringContainsString('tx_metricsexporter_request_size_bytes{method="GET",quantile="0.5"} 250', $responseBody);
        self::assertStringContainsString('tx_metricsexporter_request_size_bytes_count{method="GET"} 4', $responseBody);
        self::assertStringContainsString('tx_metricsexporter_request_size_bytes_sum{method="GET"} 1850', $responseBody);
    }

    /**
     * @throws MetricsRegistrationException
     */
    #[Test]
    public function testMultipleMetricTypes(): void
    {
        $this->setUpFrontendRootPage(1);

        // Register and use multiple metric types
        $counter = $this->collectorService->getRegistry()->getOrRegisterCounter(
            'tx_metricsexporter',
            'api_calls_total',
            'Total API calls',
            ['api']
        );
        $counter->inc(['users']);
        $counter->inc(['products']);

        $gauge = $this->collectorService->getRegistry()->getOrRegisterGauge(
            'tx_metricsexporter',
            'memory_usage_bytes',
            'Current memory usage in bytes'
        );
        $gauge->set(1024 * 1024 * 100); // 100MB

        $histogram = $this->collectorService->getRegistry()->getOrRegisterHistogram(
            'tx_metricsexporter',
            'database_query_seconds',
            'Database query time in seconds',
            ['query_type'],
            [0.001, 0.01, 0.1, 1]
        );
        $histogram->observe(0.005, ['select']);
        $histogram->observe(0.05, ['update']);

        $request = (new InternalRequest());
        $request = $request->withMethod('GET')->withQueryParameter('type', '1717676395');

        $response = $this->executeFrontendSubRequest($request);

        // Verify the response was successful
        self::assertEquals(200, $response->getStatusCode());

        $responseBody = (string)$response->getBody();

        // Verify all metrics are present
        self::assertStringContainsString('# TYPE tx_metricsexporter_api_calls_total counter', $responseBody);
        self::assertStringContainsString('# TYPE tx_metricsexporter_memory_usage_bytes gauge', $responseBody);
        self::assertStringContainsString('# TYPE tx_metricsexporter_database_query_seconds histogram', $responseBody);

        // Check specific values
        self::assertStringContainsString('tx_metricsexporter_api_calls_total{api="users"} 1', $responseBody);
        self::assertStringContainsString('tx_metricsexporter_api_calls_total{api="products"} 1', $responseBody);
        self::assertStringContainsString('tx_metricsexporter_memory_usage_bytes 104857600', $responseBody);
        self::assertStringContainsString('tx_metricsexporter_database_query_seconds_bucket{query_type="select",le="0.01"} 1', $responseBody);
        self::assertStringContainsString('tx_metricsexporter_database_query_seconds_bucket{query_type="update",le="0.1"} 1', $responseBody);
    }
}
