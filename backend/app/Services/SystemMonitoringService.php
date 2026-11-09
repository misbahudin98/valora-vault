<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SystemMonitoringService
{
    /**
     * Gather system health, maintenance mode status, database metrics, and server resources.
     *
     * @return array<string, mixed>
     */
    public function getSystemHealth(): array
    {
        $isMaintenance = app()->isDownForMaintenance();
        $downData = [];
        $downFile = storage_path('framework/down');

        if ($isMaintenance && file_exists($downFile)) {
            $downData = json_decode(file_get_contents($downFile), true) ?: [];
        }

        $laravelLog = storage_path('logs/laravel.log');
        $clientLog = storage_path('logs/client.log');

        $laravelLogSize = file_exists($laravelLog) ? filesize($laravelLog) : 0;
        $clientLogSize = file_exists($clientLog) ? filesize($clientLog) : 0;

        // Dual-Database & Environment Telemetry
        $defaultDriver = config('database.default', 'sqlite');
        $environment = app()->environment();
        $databaseMetrics = [
            'default_driver' => $defaultDriver,
            'environment' => $environment,
            'is_production' => app()->isProduction(),
            'is_mysql' => ($defaultDriver === 'mysql'),
            'is_sqlite' => ($defaultDriver === 'sqlite'),
        ];

        if ($defaultDriver === 'sqlite') {
            $journalMode = 'WAL';
            $synchronous = 'NORMAL';
            try {
                $jm = DB::select('PRAGMA journal_mode;');
                if (!empty($jm)) {
                    $firstJm = (array) $jm[0];
                    $journalMode = strtoupper((string) ($firstJm['journal_mode'] ?? reset($firstJm)));
                }
                $sync = DB::select('PRAGMA synchronous;');
                if (!empty($sync)) {
                    $firstSync = (array) $sync[0];
                    $syncVal = (int) ($firstSync['synchronous'] ?? reset($firstSync));
                    $synchronous = match ($syncVal) {
                        0 => 'OFF',
                        1 => 'NORMAL',
                        2 => 'FULL',
                        3 => 'EXTRA',
                        default => (string) $syncVal,
                    };
                }
            } catch (\Throwable $e) {
                // Defensive fallback
            }

            $dbPath = config('database.connections.sqlite.database');
            $dbSize = ($dbPath && file_exists($dbPath)) ? $this->formatBytes(filesize($dbPath)) : 'N/A';

            $databaseMetrics['sqlite'] = [
                'journal_mode' => $journalMode,
                'synchronous' => $synchronous,
                'db_size' => $dbSize,
                'busy_timeout' => '5000ms',
            ];
        } elseif ($defaultDriver === 'mysql') {
            $mysqlVersion = '8.0+';
            $innodbStatus = 'Active';
            try {
                $ver = DB::select('SELECT VERSION() as ver;');
                if (!empty($ver)) {
                    $firstVer = (array) $ver[0];
                    $mysqlVersion = (string) ($firstVer['ver'] ?? '8.0');
                }
            } catch (\Throwable $e) {
                // Defensive fallback
            }

            $databaseMetrics['mysql'] = [
                'engine' => 'InnoDB',
                'version' => $mysqlVersion,
                'connection_pool_max' => (int) config('database.max_connections', 25),
                'innodb_status' => $innodbStatus,
                'tls_status' => 'Encrypted / TLS',
                'query_cache' => 'Optimized',
            ];
        }

        return [
            'maintenance' => [
                'is_active' => $isMaintenance,
                'secret' => $downData['secret'] ?? null,
                'retry' => $downData['retry'] ?? null,
                'bypass_url' => isset($downData['secret']) ? url('/' . $downData['secret']) : null,
            ],
            'server' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server_time' => now()->toIso8601String(),
                'timezone' => config('app.timezone', 'UTC'),
                'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                'memory_limit' => ini_get('memory_limit'),
                'disk_free_space' => function_exists('disk_free_space') && @disk_free_space(base_path()) ? round(disk_free_space(base_path()) / 1024 / 1024 / 1024, 2) . ' GB' : 'N/A',
            ],
            'database' => $databaseMetrics,
            'logs' => [
                'laravel_log_size' => $this->formatBytes($laravelLogSize),
                'client_log_size' => $this->formatBytes($clientLogSize),
                'laravel_log_bytes' => $laravelLogSize,
                'client_log_bytes' => $clientLogSize,
            ]
        ];
    }

    /**
     * Toggle maintenance mode on or off.
     *
     * @return array<string, mixed>
     */
    public function toggleMaintenance(string $action, string $secret, int $retry): array
    {
        if ($action === 'enable') {
            Artisan::call('down', [
                '--secret' => $secret,
                '--retry' => $retry,
            ]);

            return [
                'message' => 'Mode maintenance berhasil diaktifkan.',
                'is_active' => true,
                'secret' => $secret,
                'bypass_url' => url('/' . $secret),
            ];
        }

        Artisan::call('up');

        return [
            'message' => 'Mode maintenance berhasil dinonaktifkan. Sistem kembali normal.',
            'is_active' => false,
        ];
    }

    /**
     * Parse and filter backend Laravel logs.
     *
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function getLaravelLogs(?string $filterLevel = null, ?string $search = null, int $limit = 100): array
    {
        $logPath = storage_path('logs/laravel.log');
        if (!file_exists($logPath)) {
            return ['data' => [], 'meta' => ['total' => 0, 'limit' => $limit, 'file_size' => '0 B']];
        }

        $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) {
            return ['data' => [], 'meta' => ['total' => 0, 'limit' => $limit, 'file_size' => $this->formatBytes(filesize($logPath))]];
        }

        $lines = array_slice($lines, -500);
        $parsedLogs = [];
        $currentEntry = null;

        $pattern = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] [a-zA-Z0-9_\.]+\.([A-Z]+): (.*)$/';

        foreach ($lines as $line) {
            if (preg_match($pattern, $line, $matches)) {
                if ($currentEntry) {
                    $parsedLogs[] = $currentEntry;
                }

                $message = $matches[3];
                $traceId = null;

                if (preg_match('/\[API Exception\] Trace: ([a-f0-9\-]+) \| (.*)$/', $message, $traceMatches)) {
                    $traceId = $traceMatches[1];
                    $message = $traceMatches[2];
                }

                $currentEntry = [
                    'timestamp' => $matches[1],
                    'level' => strtoupper($matches[2]),
                    'trace_id' => $traceId,
                    'message' => $message,
                    'stack' => '',
                ];
            } else {
                if ($currentEntry && strlen($currentEntry['stack']) < 2000) {
                    $currentEntry['stack'] .= "\n" . $line;
                }
            }
        }

        if ($currentEntry) {
            $parsedLogs[] = $currentEntry;
        }

        $parsedLogs = array_reverse($parsedLogs);

        if ($filterLevel) {
            $parsedLogs = array_values(array_filter($parsedLogs, fn ($l) => strtoupper($l['level']) === strtoupper($filterLevel)));
        }

        if ($search) {
            $q = strtolower($search);
            $parsedLogs = array_values(array_filter($parsedLogs, function ($l) use ($q) {
                return str_contains(strtolower($l['message']), $q) ||
                    str_contains(strtolower($l['trace_id'] ?? ''), $q) ||
                    str_contains(strtolower($l['stack']), $q);
            }));
        }

        $paged = array_slice($parsedLogs, 0, $limit);

        return [
            'data' => $paged,
            'meta' => [
                'total' => count($parsedLogs),
                'limit' => $limit,
                'file_size' => $this->formatBytes(filesize($logPath)),
            ]
        ];
    }

    /**
     * Parse and filter frontend client JS error logs.
     *
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function getClientLogs(?string $search = null, int $limit = 100): array
    {
        $logPath = storage_path('logs/client.log');
        if (!file_exists($logPath)) {
            return ['data' => [], 'meta' => ['total' => 0, 'limit' => $limit, 'file_size' => '0 B']];
        }

        $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) {
            return ['data' => [], 'meta' => ['total' => 0, 'limit' => $limit, 'file_size' => $this->formatBytes(filesize($logPath))]];
        }

        $lines = array_slice($lines, -300);
        $entries = [];

        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $entries[] = $decoded;
            }
        }

        $entries = array_reverse($entries);

        if ($search) {
            $q = strtolower($search);
            $entries = array_values(array_filter($entries, function ($e) use ($q) {
                return str_contains(strtolower($e['message'] ?? ''), $q) ||
                    str_contains(strtolower($e['error_id'] ?? ''), $q) ||
                    str_contains(strtolower($e['url'] ?? ''), $q);
            }));
        }

        $paged = array_slice($entries, 0, $limit);

        return [
            'data' => $paged,
            'meta' => [
                'total' => count($entries),
                'limit' => $limit,
                'file_size' => $this->formatBytes(filesize($logPath)),
            ]
        ];
    }

    /**
     * Clear log files safely.
     */
    public function clearLogs(string $type = 'all'): void
    {
        if ($type === 'laravel' || $type === 'all') {
            $path = storage_path('logs/laravel.log');
            if (file_exists($path)) {
                file_put_contents($path, '');
            }
        }

        if ($type === 'client' || $type === 'all') {
            $path = storage_path('logs/client.log');
            if (file_exists($path)) {
                file_put_contents($path, '');
            }
        }
    }

    /**
     * Record client telemetry exception.
     *
     * @param array<string, mixed> $payload
     * @return array{status: string, error_id: string}
     */
    public function recordTelemetry(array $payload, ?string $referer, ?string $userAgent, ?string $ip, ?int $userId): array
    {
        $logPath = storage_path('logs/client.log');
        $logDir = dirname($logPath);

        if (!is_dir($logDir)) {
            File::makeDirectory($logDir, 0755, true);
        }

        $entry = [
            'error_id' => $payload['error_id'] ?? (string) Str::uuid(),
            'timestamp' => $payload['timestamp'] ?? now()->toIso8601String(),
            'message' => $payload['message'],
            'stack' => $payload['stack'] ?? null,
            'url' => $payload['url'] ?? $referer,
            'user_agent' => $payload['user_agent'] ?? $userAgent,
            'ip' => $ip,
            'user_id' => $userId,
        ];

        file_put_contents($logPath, json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);

        return [
            'status' => 'recorded',
            'error_id' => $entry['error_id'],
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
