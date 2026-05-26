<?php

namespace PeterSowah\Heimdall\Services\Checkers;

use Exception;
use PeterSowah\Heimdall\Models\Domain;

class SslCheckerService
{
    public function check(Domain $domain): array
    {
        try {
            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $socket = @stream_socket_client(
                "ssl://{$domain->name}:443",
                $errno,
                $errstr,
                10,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if (! $socket) {
                return [
                    'status' => 'error',
                    'value' => null,
                    'message' => "Cannot connect: {$errstr}",
                    'raw_data' => [],
                ];
            }

            $params = stream_context_get_params($socket);
            $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
            fclose($socket);

            if (! $cert) {
                return [
                    'status' => 'error',
                    'value' => null,
                    'message' => 'Could not parse SSL certificate',
                    'raw_data' => [],
                ];
            }

            $validTo = $cert['validTo_time_t'];
            $daysUntilExpiry = (int) ceil(($validTo - time()) / 86400);

            $status = match (true) {
                $daysUntilExpiry <= 7 => 'critical',
                $daysUntilExpiry <= 30 => 'warning',
                default => 'ok',
            };

            return [
                'status' => $status,
                'value' => $daysUntilExpiry,
                'message' => "SSL expires in {$daysUntilExpiry} days ({$cert['subject']['CN']})",
                'raw_data' => [
                    'subject' => $cert['subject'] ?? [],
                    'issuer' => $cert['issuer'] ?? [],
                    'valid_from' => $cert['validFrom_time_t'] ?? null,
                    'valid_to' => $cert['validTo_time_t'] ?? null,
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
}
