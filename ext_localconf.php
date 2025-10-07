<?php

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

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['prometheus_storage'] ??= [];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['prometheus_storage']['backend']
            ??= Typo3DatabaseBackend::class;
    }
);
