<?php

declare(strict_types=1);

namespace AUS\MetricsExporter\Event;

use Psr\Http\Message\StreamInterface;

class WriteStreamEvent
{
    public function __construct(
        private StreamInterface $stream
    ) {
    }

    public function write(string $data): void
    {
        $this->stream->write(PHP_EOL . $data);
    }
}
