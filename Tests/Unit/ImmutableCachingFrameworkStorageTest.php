<?php

declare(strict_types=1);

namespace AUS\MetricsExporter\Tests\Unit;

use AUS\MetricsExporter\Storage\ImmutableCachingFrameworkStorage;
use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use RuntimeException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

final class ImmutableCachingFrameworkStorageTest extends TestCase
{
    public function testMetricsRoundTripThroughCache(): void
    {
        /** @var array<string, mixed> $values */
        $values = [];
        $cache = $this->createStub(FrontendInterface::class);
        $cache->method('get')->willReturnCallback(static function (string $key) use (&$values): mixed {
            return $values[$key] ?? false;
        });
        $cache->method('set')->willReturnCallback(static function (string $key, mixed $value) use (&$values): void {
            $values[$key] = $value;
        });
        $registry = new CollectorRegistry(new ImmutableCachingFrameworkStorage($cache), false);
        $registry->getOrRegisterCounter('test', 'requests_total', 'Requests', ['kind'])->incBy(2, ['read']);
        $registry->getOrRegisterGauge('test', 'queue_size', 'Queue size')->set(3);
        $registry->getOrRegisterHistogram('test', 'duration', 'Duration', [], [0.5, 1.0])->observe(0.25);
        $registry->getOrRegisterSummary('test', 'summary', 'Summary')->observe(0.75);

        $rendered = (new RenderTextFormat())->render($registry->getMetricFamilySamples());

        self::assertStringContainsString('test_requests_total{kind="read"} 2', $rendered);
        self::assertStringContainsString('test_queue_size 3', $rendered);
        self::assertStringContainsString('test_duration_bucket{le="0.5"} 1', $rendered);
        self::assertStringContainsString('test_summary_count 1', $rendered);
        self::assertStringContainsString('test_summary_sum 0.75', $rendered);
    }

    public function testRejectsInvalidCacheData(): void
    {
        $cache = $this->createStub(FrontendInterface::class);
        $cache->method('get')->willReturn('invalid');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cache returned invalid data');

        (new ImmutableCachingFrameworkStorage($cache))->collect();
    }

    public function testRejectsInvalidLabelValues(): void
    {
        $cache = $this->createStub(FrontendInterface::class);
        $labels = base64_encode(json_encode([true], JSON_THROW_ON_ERROR));
        $cache->method('get')->willReturn([
            'counter:test:meta' => [
                'meta' => ['name' => 'test', 'help' => 'Test', 'type' => 'counter', 'labelNames' => ['label']],
                'samples' => ['counter:test:' . $labels . ':value' => 1],
            ],
        ]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot decode label values');

        (new ImmutableCachingFrameworkStorage($cache))->collect();
    }
}
