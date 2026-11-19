<?php

declare(strict_types=1);

namespace AUS\MetricsExporter\Service;

class SecurityService
{
    private function ipMatchesCidr(string $ip, string $cidr): bool
    {
        // Handle single IP without CIDR notation
        if (!str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $bits] = explode('/', $cidr);
        $bits = (int)$bits;

        // IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            if ($ipLong === false || $subnetLong === false || $bits < 0 || $bits > 32) {
                return false;
            }

            $mask = -1 << (32 - $bits);

            return ($ipLong & $mask) === ($subnetLong & $mask);
        }

        // IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ipBin = inet_pton($ip);
            $subnetBin = inet_pton($subnet);

            if ($ipBin === false || $subnetBin === false || $bits < 0 || $bits > 128) {
                return false;
            }

            $mask = str_repeat("\xff", (int)($bits / 8));
            $remaining = $bits % 8;
            if ($remaining > 0) {
                $mask .= chr(0xff << (8 - $remaining));
            }

            $mask = str_pad($mask, 16, "\x00");

            return ($ipBin & $mask) === ($subnetBin & $mask);
        }

        return false;
    }

    /**
     * @param list<string> $cidrs
     */
    public function isRequestAllowed(string $ip, array $cidrs): bool
    {
        foreach ($cidrs as $cidr) {
            if ($this->ipMatchesCidr($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }
}
