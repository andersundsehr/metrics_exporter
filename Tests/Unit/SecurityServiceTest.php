<?php

declare(strict_types=1);

namespace AUS\MetricsExporter\Tests\Unit;

use AUS\MetricsExporter\Service\SecurityService;
use PHPUnit\Framework\TestCase;

final class SecurityServiceTest extends TestCase
{
    public function testIpv6PrefixesWithPartialBytes(): void
    {
        $service = new SecurityService();

        self::assertTrue($service->isRequestAllowed('8000::1', ['8000::/1']));
        self::assertFalse($service->isRequestAllowed('::1', ['8000::/1']));
        self::assertTrue($service->isRequestAllowed('2001:db8::1', ['2000::/7']));
        self::assertFalse($service->isRequestAllowed('2200::1', ['2000::/7']));
    }

    public function testIpv6PrefixBoundaries(): void
    {
        $service = new SecurityService();

        self::assertTrue($service->isRequestAllowed('2001:db8::1', ['::/0']));
        self::assertTrue($service->isRequestAllowed('2001:db8::1', ['2001:db8::1/128']));
        self::assertFalse($service->isRequestAllowed('2001:db8::2', ['2001:db8::1/128']));
        self::assertFalse($service->isRequestAllowed('2001:db8::1', ['2001:db8::/129']));
    }
}
