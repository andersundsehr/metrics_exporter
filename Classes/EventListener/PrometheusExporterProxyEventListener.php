<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace AUS\MetricsExporter\EventListener;

use AUS\MetricsExporter\Event\WriteStreamEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Proxies Prometheus metrics from mysqld-exporter and valkey-exporter
 * into the metrics stream. Each exporter is fetched via HTTP with a short
 * timeout so that an unreachable service never blocks the response.
 */
class PrometheusExporterProxyEventListener extends AbstractEventListener
{
    /** Timeout in seconds for connection + reading */
    private const int TIMEOUT_SECONDS = 2;

    public function __invoke(WriteStreamEvent $event): void
    {
        if (!$this->config['enableExporterProxyExporter'] || !isset($this->config['exporterProxyExporterUrls'])) {
            return;
        }

        $exporterProxyExporterUrls = $this->config['exporterProxyExporterUrls'];
        assert(is_string($exporterProxyExporterUrls));

        /**
         * @var array<string, string> $urls
         */
        $urls = GeneralUtility::trimExplode(',', $exporterProxyExporterUrls, true);
        if (!$urls) {
            return;
        }

        foreach ($urls as $i => $url) {
            $content = $this->fetchMetrics($url);
            if ($content === null) {
                $event->write('# Scrape target no ' . $i . ' unreachable or timed out');
                continue;
            }

            // Strip the trailing newline so write() can add its own separator
            $event->write(rtrim($content));
        }
    }

    /**
     * Fetches raw Prometheus metrics text from the given URL.
     * Returns null when the host is unreachable or the request times out.
     */
    private function fetchMetrics(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'method'          => 'GET',
                'timeout'         => self::TIMEOUT_SECONDS,
                'ignore_errors'   => true,
            ],
            'socket' => [
                'tcp_nodelay' => true,
            ],
        ]);

        set_error_handler(static function (): bool {
            return true; // suppress PHP warnings from file_get_contents
        });

        try {
            $content = file_get_contents($url, false, $context);
        } finally {
            restore_error_handler();
        }

        if ($content === false || $content === '') {
            return null;
        }

        // Verify we actually received a 200 response
        $responseHeaders = $http_response_header;
        foreach ($responseHeaders as $header) {
            if (str_starts_with($header, 'HTTP/')) {
                if (!str_contains($header, ' 200 ')) {
                    return null;
                }

                break;
            }
        }

        return $content;
    }
}
