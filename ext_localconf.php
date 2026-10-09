<?php

declare(strict_types=1);

use AUS\MetricsExporter\Controller\ExposeController;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || die();

call_user_func(
    static function (): void {
        ExtensionUtility::configurePlugin(
            'AusMetricsExpoter',
            'Expose',
            [
                ExposeController::class => 'list',
            ],
            [
                ExposeController::class => 'list',
            ],
            ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
        );

        ExtensionManagementUtility::addTypoScriptSetup(
            '@import "EXT:metrics_exporter/Configuration/TypoScript/setup.typoscript"'
        );

        /** @var array{SYS: array{caching: array{cacheConfigurations: array<string, array<string, mixed>>}}} $configuration */
        $configuration = &$GLOBALS['TYPO3_CONF_VARS'];
        $configuration['SYS']['caching']['cacheConfigurations']['prometheus_storage'] ??= [];
        $configuration['SYS']['caching']['cacheConfigurations']['prometheus_storage']['backend']
            ??= Typo3DatabaseBackend::class;
    }
);
