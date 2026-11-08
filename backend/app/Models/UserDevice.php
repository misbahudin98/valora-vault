<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDevice extends Model
{
    use HasFactory;

    protected $table = 'user_devices';

    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'device_name',
        'device_type',
        'platform',
        'browser',
        'token_version',
        'last_active_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'last_active_at' => 'datetime',
            'expires_at' => 'datetime',
            'token_version' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Parse User-Agent string to extract human-readable device, browser, and platform metadata.
     */
    public static function parseDeviceMetadata(?string $userAgent): array
    {
        if (empty($userAgent)) {
            return [
                'platform' => 'Unknown',
                'browser' => 'Unknown',
                'device_type' => 'desktop',
                'device_name' => 'Unknown Device',
            ];
        }

        $ua = strtolower($userAgent);

        // Platform detection
        $platform = 'Windows';
        if (str_contains($ua, 'iphone') || str_contains($ua, 'ipad') || str_contains($ua, 'ipod')) {
            $platform = 'iOS';
        } elseif (str_contains($ua, 'android')) {
            $platform = 'Android';
        } elseif (str_contains($ua, 'macintosh') || str_contains($ua, 'mac os')) {
            $platform = 'macOS';
        } elseif (str_contains($ua, 'linux')) {
            $platform = 'Linux';
        }

        // Device Type
        $deviceType = 'desktop';
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            $deviceType = 'mobile';
        } elseif (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            $deviceType = 'tablet';
        }

        // Browser Detection
        $browser = 'Browser';
        if (str_contains($ua, 'edg/') || str_contains($ua, 'edge/')) {
            $browser = 'Edge';
        } elseif (str_contains($ua, 'chrome/') || str_contains($ua, 'crios/')) {
            $browser = 'Chrome';
        } elseif (str_contains($ua, 'firefox/') || str_contains($ua, 'fxios/')) {
            $browser = 'Firefox';
        } elseif (str_contains($ua, 'safari/') && !str_contains($ua, 'chrome/')) {
            $browser = 'Safari';
        } elseif (str_contains($ua, 'opr/') || str_contains($ua, 'opera/')) {
            $browser = 'Opera';
        }

        $deviceName = "{$browser} on {$platform}";

        return [
            'platform' => $platform,
            'browser' => $browser,
            'device_type' => $deviceType,
            'device_name' => $deviceName,
        ];
    }

    /**
     * Get count of physical session files currently stored on disk.
     */
    public static function countDiskSessions(): int
    {
        $sessionPath = storage_path('framework/sessions');
        if (!is_dir($sessionPath)) {
            return 0;
        }

        $count = 0;
        $dirHandle = @opendir($sessionPath);
        if (!$dirHandle) {
            return 0;
        }

        while (($entry = readdir($dirHandle)) !== false) {
            if ($entry !== '.' && $entry !== '..' && $entry !== '.gitignore' && $entry !== '.gitkeep') {
                $count++;
            }
        }
        closedir($dirHandle);

        return $count;
    }

    /**
     * Enterprise Session File Garbage Collector:
     * Prune orphaned, guest CSRF, and expired session files accumulating in storage/framework/sessions.
     * Prevents inode exhaustion and directory scan degradation on shared hosting.
     *
     * @param int $graceMinutes Grace period in minutes for unauthenticated session files (default 60 min).
     * @param bool $force If true, purges all unindexed session files immediately.
     * @return array Scanned, deleted, bytes_freed, and active_retained counts.
     */
    public static function pruneDiskSessions(int $graceMinutes = 60, bool $force = false): array
    {
        $sessionPath = storage_path('framework/sessions');
        $result = [
            'scanned' => 0,
            'deleted' => 0,
            'bytes_freed' => 0,
            'active_retained' => 0,
        ];

        if (!is_dir($sessionPath)) {
            return $result;
        }

        // Active session IDs in database with non-expired TTL
        $activeSessionIds = self::where('expires_at', '>', now())
            ->whereNotNull('session_id')
            ->pluck('session_id')
            ->toArray();
        $activeMap = array_flip($activeSessionIds);

        $now = time();
        $sessionLifetimeSeconds = ((int) config('session.lifetime', 120)) * 60;
        $graceSeconds = $graceMinutes * 60;

        $dirHandle = @opendir($sessionPath);
        if (!$dirHandle) {
            return $result;
        }

        while (($entry = readdir($dirHandle)) !== false) {
            if ($entry === '.' || $entry === '..' || $entry === '.gitignore' || $entry === '.gitkeep') {
                continue;
            }

            $filePath = $sessionPath . DIRECTORY_SEPARATOR . $entry;
            if (!is_file($filePath)) {
                continue;
            }

            $result['scanned']++;
            $mtime = @filemtime($filePath) ?: $now;
            $fileAgeSeconds = $now - $mtime;
            $fileSize = @filesize($filePath) ?: 0;

            $shouldDelete = false;

            if (isset($activeMap[$entry])) {
                // Tracked in user_devices: verify if beyond hard session lifetime
                if ($fileAgeSeconds > $sessionLifetimeSeconds) {
                    $shouldDelete = true;
                    self::where('session_id', $entry)->delete();
                } else {
                    $result['active_retained']++;
                }
            } else {
                // Orphaned session file (CSRF cookie, guest visit, test run, or previously revoked)
                if ($force) {
                    $shouldDelete = true;
                } else {
                    $content = @file_get_contents($filePath);
                    $isAuthenticated = $content && str_contains($content, 'login_web_');
                    if ($isAuthenticated) {
                        // An authenticated session file not indexed in user_devices is an orphan; purge immediately
                        $shouldDelete = true;
                    } elseif ($fileAgeSeconds > $graceSeconds) {
                        $shouldDelete = true;
                    }
                }
            }

            if ($shouldDelete) {
                if (@unlink($filePath)) {
                    $result['deleted']++;
                    $result['bytes_freed'] += $fileSize;
                }
            }
        }

        closedir($dirHandle);

        return $result;
    }

    /**
     * Auto-remove expired or invalid session tokens from the registry.
     */
    public static function purgeStaleSessions(?int $userId = null, bool $pruneDisk = true): int
    {
        $deletedCount = 0;

        // 1. Purge expired sessions
        $expiredQuery = self::where('expires_at', '<', now());
        if ($userId !== null) {
            $expiredQuery->where('user_id', $userId);
        }
        $expiredDevices = $expiredQuery->get();
        foreach ($expiredDevices as $device) {
            if ($device->session_id) {
                $sessionFile = storage_path("framework/sessions/{$device->session_id}");
                if (file_exists($sessionFile)) {
                    @unlink($sessionFile);
                }
            }
            $device->delete();
            $deletedCount++;
        }

        // 2. Purge sessions with obsolete token version
        $obsoleteQuery = self::whereHas('user', function ($q) {
            $q->whereColumn('user_devices.token_version', '<', 'users.token_version');
        });
        if ($userId !== null) {
            $obsoleteQuery->where('user_id', $userId);
        }
        $obsoleteDevices = $obsoleteQuery->get();
        foreach ($obsoleteDevices as $device) {
            if ($device->session_id) {
                $sessionFile = storage_path("framework/sessions/{$device->session_id}");
                if (file_exists($sessionFile)) {
                    @unlink($sessionFile);
                }
            }
            $device->delete();
            $deletedCount++;
        }

        // 3. Prune orphaned disk session files (grace period 10 minutes)
        if ($pruneDisk) {
            self::pruneDiskSessions(graceMinutes: 10);
        }

        return $deletedCount;
    }
}
