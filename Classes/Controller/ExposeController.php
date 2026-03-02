<?php

declare(strict_types=1);

namespace AUS\MetricsExporter\Controller;

use AUS\MetricsExporter\Event\BeforeMetricsRenderEvent;
use AUS\MetricsExporter\Event\WriteStreamEvent;
use AUS\MetricsExporter\Service\CollectorService;
use Prometheus\RenderTextFormat;
use Throwable;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class ExposeController extends ActionController
{
    public function __construct(
        private readonly CollectorService $collectorService,
        private readonly BeforeMetricsRenderEvent $beforeMetricsRenderEvent
    ) {
    }

    /**
     * @throws Throwable
     * @noinspection PhpUnused
     */
    public function listAction(): Response
    {
        $renderer = new RenderTextFormat();
        $registry = $this->collectorService->getRegistry();

        $this->eventDispatcher->dispatch($this->beforeMetricsRenderEvent);
        $result = $renderer->render($registry->getMetricFamilySamples(false));

        $stream = GeneralUtility::makeInstance(StreamFactory::class)->createStream($result);
        $this->eventDispatcher->dispatch(new WriteStreamEvent($stream));
        return (new Response())->withBody($stream)->withHeader('Content-Type', RenderTextFormat::MIME_TYPE)->withHeader('Content-Disposition', 'inline');
    }
}
