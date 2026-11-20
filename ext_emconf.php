<?php

/** @noinspection PhpUndefinedVariableInspection */

// @phpstan-ignore-next-line
$EM_CONF[$_EXTKEY] = [
    'title' => 'anders und sehr: Metrics Exporter',
    'description' => 'Exports metrics to a well-known place where you can scrape them',
    'category' => 'distribution',
    'author' => 'Development team - anders und sehr',
    'author_email' => 'tech@andersundsehr.com',
    'author_company' => 'anders und sehr GmbH',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
