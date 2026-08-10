<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace AUS\MetricsExporter\EventListener;

use AUS\MetricsExporter\Event\WriteStreamEvent;

class PrometheusFpmEventListener extends AbstractEventListener
{
    public function __invoke(WriteStreamEvent $event): void
    {
        if (!$this->config['enableFpmExporter']) {
            return;
        }

        if (!function_exists('fpm_get_status') || !$fpmStatus = fpm_get_status()) {
            error_log('Can not scrape PHP-FPM status');
            return;
        }

        $status = [];

        $status[] = '# TYPE phpfpm_up gauge';
        $status[] = 'phpfpm_up ' . $fpmStatus['start-time'];

        $status[] = '# TYPE phpfpm_start_since counter';
        $status[] = 'phpfpm_start_since ' . $fpmStatus['start-since'];

        $status[] = '# TYPE phpfpm_accepted_connections counter';
        $status[] = 'phpfpm_accepted_connections ' . $fpmStatus['accepted-conn'];

        $status[] = '# TYPE phpfpm_listen_queue gauge';
        $status[] = 'phpfpm_listen_queue ' . $fpmStatus['listen-queue'];

        $status[] = '# TYPE phpfpm_max_listen_queue counter';
        $status[] = 'phpfpm_max_listen_queue ' . $fpmStatus['max-listen-queue'];

        $status[] = '# HELP phpfpm_listen_queue_length The size of the socket queue of pending connections.';
        $status[] = 'phpfpm_listen_queue_length ' . $fpmStatus['listen-queue-len'];

        $status[] = '# TYPE phpfpm_idle_processes gauge';
        $status[] = 'phpfpm_idle_processes ' . $fpmStatus['idle-processes'];

        $status[] = '# TYPE phpfpm_active_processes gauge';
        $status[] = 'phpfpm_active_processes ' . $fpmStatus['active-processes'];

        $status[] = '# TYPE phpfpm_total_processes gauge';
        $status[] = 'phpfpm_total_processes ' . $fpmStatus['total-processes'];

        $status[] = '# TYPE phpfpm_max_active_processes counter';
        $status[] = 'phpfpm_max_active_processes ' . $fpmStatus['max-active-processes'];

        $status[] = '# TYPE phpfpm_max_children_reached counter';
        $status[] = 'phpfpm_max_children_reached ' . (int)$fpmStatus['max-children-reached'];

        $status[] = '# TYPE phpfpm_slow_requests counter';
        $status[] = 'phpfpm_slow_requests ' . $fpmStatus['slow-requests'];

        $event->write(implode(PHP_EOL, $status));
    }
}
