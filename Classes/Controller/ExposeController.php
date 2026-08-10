<?php

declare(strict_types=1);

namespace AUS\MetricsExporter\Controller;

use AUS\MetricsExporter\Event\BeforeMetricsRenderEvent;
use AUS\MetricsExporter\Event\WriteStreamEvent;
use AUS\MetricsExporter\Service\CollectorService;
use AUS\MetricsExporter\Service\SecurityService;
use Prometheus\RenderTextFormat;
use Throwable;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class ExposeController extends ActionController
{
    public function __construct(
        private readonly CollectorService $collectorService,
        private readonly BeforeMetricsRenderEvent $beforeMetricsRenderEvent,
        private readonly SecurityService $securityService,
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {
    }

    /**
     * @throws Throwable
     * @noinspection PhpUnused
     */
    public function listAction(): Response
    {
        $configuration = $this->extensionConfiguration->get('metrics_exporter');
        assert(is_array($configuration));
        $cidrs = $configuration['allowedCidrs'] ?? '';
        assert(is_string($cidrs));
        $cidrs = GeneralUtility::trimExplode(',', $cidrs, true);

        $ip = GeneralUtility::getIndpEnv('REMOTE_ADDR');
        if ($cidrs && (!$ip || !is_string($ip) || !$this->securityService->isRequestAllowed($ip, $cidrs))) {
            return (new Response())->withStatus(403)->withHeader('Content-Type', 'text/plain')
                ->withBody(GeneralUtility::makeInstance(StreamFactory::class)
                ->createStream('Forbidden'));
        }

        $renderer = new RenderTextFormat();
        $registry = $this->collectorService->getRegistry();

        $this->eventDispatcher->dispatch($this->beforeMetricsRenderEvent);
        $result = $renderer->render($registry->getMetricFamilySamples(false));

        $stream = GeneralUtility::makeInstance(StreamFactory::class)->createStream($result);
        $this->eventDispatcher->dispatch(new WriteStreamEvent($stream));
        return (new Response())->withBody($stream)->withHeader('Content-Type', RenderTextFormat::MIME_TYPE)->withHeader('Content-Disposition', 'inline');
    }
}
