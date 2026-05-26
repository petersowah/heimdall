<?php

namespace PeterSowah\Heimdall\Services\Checkers;

use Exception;
use PeterSowah\Heimdall\Models\Domain;

class DnsCheckerService
{
    public function check(Domain $domain): array
    {
        try {
            $records = [];

            foreach ([DNS_A, DNS_MX, DNS_NS, DNS_CNAME] as $type) {
                $result = @dns_get_record($domain->name, $type);
                if ($result !== false) {
                    $records = array_merge($records, $result);
                }
            }

            if (empty($records)) {
                return [
                    'status' => 'critical',
                    'value' => null,
                    'message' => 'No DNS records found',
                    'raw_data' => [],
                ];
            }

            return [
                'status' => 'ok',
                'value' => count($records),
                'message' => count($records).' DNS records found',
                'raw_data' => ['records' => $records],
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
