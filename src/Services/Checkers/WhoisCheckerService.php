<?php

namespace PeterSowah\Heimdall\Services\Checkers;

use Exception;
use Illuminate\Support\Facades\Cache;
use PeterSowah\Heimdall\Models\Domain;

class WhoisCheckerService
{
    public function check(Domain $domain): array
    {
        $cacheKey = "heimdall:whois:{$domain->name}";

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($domain) {
            return $this->performWhoisLookup($domain->name);
        });
    }

    private function performWhoisLookup(string $domainName): array
    {
        try {
            $tld = strtolower(substr(strrchr($domainName, '.'), 1));
            $whoisServer = $this->resolveWhoisServer($tld);

            $socket = @fsockopen($whoisServer, 43, $errno, $errstr, 10);
            if (! $socket) {
                return [
                    'status' => 'error',
                    'value' => null,
                    'message' => "WHOIS connection failed: {$errstr}",
                    'raw_data' => [],
                ];
            }

            fwrite($socket, "{$domainName}\r\n");

            $rawResponse = '';
            while (! feof($socket)) {
                $rawResponse .= fgets($socket, 512);
            }
            fclose($socket);

            $expiryDate = $this->parseExpiryDate($rawResponse);

            if (! $expiryDate) {
                return [
                    'status' => 'warning',
                    'value' => null,
                    'message' => 'Could not parse expiry date from WHOIS response',
                    'raw_data' => ['raw' => substr($rawResponse, 0, 2000)],
                ];
            }

            $daysUntilExpiry = (int) ceil(($expiryDate - time()) / 86400);

            $status = match (true) {
                $daysUntilExpiry <= 7 => 'critical',
                $daysUntilExpiry <= 30 => 'warning',
                default => 'ok',
            };

            return [
                'status' => $status,
                'value' => $daysUntilExpiry,
                'message' => "Domain expires in {$daysUntilExpiry} days",
                'raw_data' => [
                    'expiry_timestamp' => $expiryDate,
                    'raw' => substr($rawResponse, 0, 2000),
                ],
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'value' => null,
                'message' => $e->getMessage(),
                'raw_data' => [],
            ];
        }
    }

    private function resolveWhoisServer(string $tld): string
    {
        $servers = [
            'com' => 'whois.verisign-grs.com',
            'net' => 'whois.verisign-grs.com',
            'org' => 'whois.publicinterestregistry.org',
            'io' => 'whois.nic.io',
            'ai' => 'whois.nic.ai',
            'co' => 'whois.nic.co',
            'uk' => 'whois.nic.uk',
            'de' => 'whois.denic.de',
            'app' => 'whois.nic.google',
            'dev' => 'whois.nic.google',
        ];

        return $servers[$tld] ?? 'whois.iana.org';
    }

    private function parseExpiryDate(string $raw): ?int
    {
        $patterns = [
            '/expir(?:y|ation|es)[^\n]*?:\s*([^\n]+)/i',
            '/Registry Expiry Date:\s*([^\n]+)/i',
            '/Expiry date:\s*([^\n]+)/i',
            '/paid-till:\s*([^\n]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $raw, $matches)) {
                $dateStr = trim($matches[1]);
                $timestamp = strtotime($dateStr);
                if ($timestamp !== false && $timestamp > 0) {
                    return $timestamp;
                }
            }
        }

        return null;
    }
}
