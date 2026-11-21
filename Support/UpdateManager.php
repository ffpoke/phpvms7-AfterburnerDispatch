<?php

declare(strict_types=1);

namespace Modules\RouteRequest\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ZipArchive;

if (!class_exists(UpdateManager::class, false)) {
    class UpdateManager
    {
        public const string MIN_PHP_VERSION = '8.3.36';
        public const string RECOMMENDED_PHP_VERSION = '8.3.37';

        /**
         * Validates every entry in a ZIP archive against Zip-Slip path traversal,
         * absolute paths, null bytes, and symlink attacks before extraction.
         *
         * @throws \RuntimeException if any unsafe entry is detected.
         */
        private static function assertSafeZipArchive(ZipArchive $zip): void
        {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $rawName = $zip->getNameIndex($i);
                if (!is_string($rawName) || $rawName === '') {
                    throw new \RuntimeException('ZIP archive contains an empty or unreadable entry name.');
                }

                // Reject null bytes
                if (str_contains($rawName, "\0")) {
                    throw new \RuntimeException('Blocked unsafe ZIP entry containing a null byte.');
                }

                // Normalize directory separators
                $normalized = str_replace('\\', '/', $rawName);

                // Reject absolute Unix/Windows/UNC paths (e.g. /etc/passwd, C:/..., //server/...)
                if (str_starts_with($normalized, '/') || preg_match('/^[A-Za-z]:\//', $normalized) === 1) {
                    throw new \RuntimeException('Blocked Zip-Slip attempt (absolute path): ' . $rawName);
                }

                // Reject any parent directory traversal segments (.. or .)
                $segments = explode('/', $normalized);
                foreach ($segments as $seg) {
                    if ($seg === '..') {
                        throw new \RuntimeException('Blocked Zip-Slip path traversal attempt: ' . $rawName);
                    }
                }

                // Reject POSIX symlinks inside ZIP archives (external_attributes mode 0120000 / 0xA000)
                $stat = $zip->statIndex($i);
                if (is_array($stat) && isset($stat['external_attributes'])) {
                    $unixMode = (((int) $stat['external_attributes']) >> 16) & 0xF000;
                    if ($unixMode === 0xA000) {
                        throw new \RuntimeException('Blocked unsafe symlink entry inside ZIP archive: ' . $rawName);
                    }
                }
            }
        }

        public const string CACHE_KEY = 'routerequest_github_update_v3_0';

        public static function getRepo(): string
        {
            return trim((string) config('routerequest.github_repo', 'ffpoke/phpvms7-RouteRequest')) ?: 'ffpoke/phpvms7-RouteRequest';
        }

        public static function getCurrentVersion(): string
        {
            return ltrim(trim((string) config('routerequest.version', '3.0.3')), 'vV');
        }

        public static function allowsPreReleases(): bool
        {
            if (!class_exists(DrmEntitlements::class, false)) {
                require_once __DIR__ . '/DrmEntitlements.php';
            }
            $ent = DrmEntitlements::current(false);
            return !empty($ent['features']['prerelease_updates']) || ($ent['tier'] ?? '') === 'flagship';
        }

        public static function check(bool $force = false): array
        {
            $allowPre = self::allowsPreReleases();
            $cacheKey = self::CACHE_KEY . ($allowPre ? '_prerelease' : '_stable');

            if ($force) {
                try {
                    Cache::forget($cacheKey);
                } catch (\Throwable $e) {}
            }

            $currentVersion = self::getCurrentVersion();
            $repo = self::getRepo();

            $defaultData = [
                'checked'          => false,
                'repo'             => $repo,
                'channel'          => $allowPre ? 'Flagship (Stable + Pre-Releases)' : 'Stable Releases Only',
                'current_version'  => $currentVersion,
                'latest_version'   => $currentVersion,
                'is_prerelease'    => false,
                'update_available' => false,
                'release_name'     => null,
                'changelog'        => null,
                'published_at'     => null,
                'download_url'     => null,
                'html_url'         => 'https://github.com/' . $repo . '/releases',
                'error'            => null,
            ];

            $fetchFn = function () use ($repo, $currentVersion, $allowPre, $defaultData) {
                try {
                    $url = $allowPre
                        ? ('https://api.github.com/repos/' . $repo . '/releases?per_page=10')
                        : ('https://api.github.com/repos/' . $repo . '/releases/latest');

                    $response = Http::withHeaders([
                        'User-Agent' => 'phpVMS7-RouteRequest-Updater/3.0.3 (ffpoke/phpvms7-RouteRequest)',
                        'Accept'     => 'application/vnd.github+json',
                    ])->timeout(8)->get($url);

                    if ($response->status() === 404) {
                        $defaultData['checked'] = true;
                        return $defaultData;
                    }

                    if (!$response->successful()) {
                        $defaultData['error'] = 'GitHub API returned HTTP ' . $response->status();
                        return $defaultData;
                    }

                    $rawBody = $response->body();
                    if ($rawBody === '' || !json_validate($rawBody)) {
                        $defaultData['checked'] = true;
                        return $defaultData;
                    }
                    $json = json_decode($rawBody, true);
                    $releaseObj = null;

                    if ($allowPre && is_array($json)) {
                        foreach ($json as $rel) {
                            if (is_array($rel) && empty($rel['draft']) && !empty($rel['tag_name'])) {
                                $releaseObj = $rel;
                                break;
                            }
                        }
                    } elseif (is_array($json) && !empty($json['tag_name'])) {
                        $releaseObj = $json;
                    }

                    if (!$releaseObj || empty($releaseObj['tag_name'])) {
                        $defaultData['checked'] = true;
                        return $defaultData;
                    }

                    $latestTag = ltrim(trim((string) $releaseObj['tag_name']), 'vV');
                    $hasUpdate = version_compare($latestTag, $currentVersion, '>');

                    $downloadUrl = $releaseObj['zipball_url'] ?? null;
                    if (!empty($releaseObj['assets']) && is_array($releaseObj['assets'])) {
                        foreach ($releaseObj['assets'] as $asset) {
                            $aName = strtolower((string) ($asset['name'] ?? ''));
                            if (str_ends_with($aName, '.zip') && !empty($asset['browser_download_url'])) {
                                $downloadUrl = $asset['browser_download_url'];
                                if (str_contains($aName, 'phpvms7-module') || str_contains($aName, 'routerequest')) {
                                    break;
                                }
                            }
                        }
                    }

                    return [
                        'checked'          => true,
                        'repo'             => $repo,
                        'channel'          => $allowPre ? 'Flagship (Stable + Pre-Releases)' : 'Stable Releases Only',
                        'current_version'  => $currentVersion,
                        'latest_version'   => $latestTag,
                        'is_prerelease'    => !empty($releaseObj['prerelease']),
                        'update_available' => $hasUpdate,
                        'release_name'     => (string) ($releaseObj['name'] ?? ('v' . $latestTag)),
                        'changelog'        => (string) ($releaseObj['body'] ?? ''),
                        'published_at'     => !empty($releaseObj['published_at']) ? date('d.M.Y H:i', strtotime($releaseObj['published_at'])) : null,
                        'download_url'     => $downloadUrl,
                        'html_url'         => (string) ($releaseObj['html_url'] ?? ('https://github.com/' . $repo . '/releases')),
                        'error'            => null,
                    ];
                } catch (\Throwable $e) {
                    $defaultData['error'] = $e->getMessage();
                    return $defaultData;
                }
            };

            try {
                return Cache::remember($cacheKey, 21600, $fetchFn);
            } catch (\Throwable $e) {
                return $fetchFn();
            }
        }

        public static function installLatestUpdate(): array
        {
            $info = self::check(true);

            if (empty($info['download_url'])) {
                return [
                    'success' => false,
                    'message' => 'No downloadable release ZIP found on GitHub (' . self::getRepo() . ').',
                ];
            }

            if (version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '<')) {
                return [
                    'success' => false,
                    'message' => 'RouteRequest v3.0.3 requires PHP ' . self::MIN_PHP_VERSION . ' or newer (PHP ' . self::RECOMMENDED_PHP_VERSION . '+ recommended). Detected PHP ' . PHP_VERSION . '.',
                ];
            }

            if (!class_exists(ZipArchive::class)) {
                return [
                    'success' => false,
                    'message' => 'PHP ZipArchive extension is not enabled on this server. Please use the Direct Download ZIP button instead.',
                ];
            }

            $modulePath = base_path('modules/RouteRequest');
            if (!is_dir($modulePath) || !is_writable($modulePath)) {
                return [
                    'success' => false,
                    'message' => 'The /modules/RouteRequest directory is not writable by PHP on this web host. Please use the Direct Download ZIP button to update manually.',
                ];
            }

            $tmpZip = storage_path('app/routerequest_update_' . time() . '.zip');
            $tmpExtractDir = storage_path('app/routerequest_extract_' . time());

            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'phpVMS7-RouteRequest-Updater/3.0.3 (ffpoke/phpvms7-RouteRequest)',
                    'Accept'     => 'application/octet-stream, application/zip, */*',
                ])->timeout(45)->get($info['download_url']);

                if (!$response->successful()) {
                    return [
                        'success' => false,
                        'message' => 'Failed to download update archive from GitHub (HTTP ' . $response->status() . ').',
                    ];
                }

                File::put($tmpZip, $response->body());

                $zip = new ZipArchive();
                if ($zip->open($tmpZip) !== true) {
                    @unlink($tmpZip);
                    return [
                        'success' => false,
                        'message' => 'Downloaded file is not a valid ZIP archive.',
                    ];
                }

                self::assertSafeZipArchive($zip);
                File::makeDirectory($tmpExtractDir, 0755, true, true);
                $zip->extractTo($tmpExtractDir);
                $zip->close();
                @unlink($tmpZip);

                $sourceDir = $tmpExtractDir;
                if (is_dir($tmpExtractDir . '/RouteRequest') && file_exists($tmpExtractDir . '/RouteRequest/module.json')) {
                    $sourceDir = $tmpExtractDir . '/RouteRequest';
                } else {
                    $subdirs = File::directories($tmpExtractDir);
                    if (count($subdirs) === 1 && file_exists($subdirs[0] . '/module.json')) {
                        $sourceDir = $subdirs[0];
                    }
                }

                if (!file_exists($sourceDir . '/module.json')) {
                    File::deleteDirectory($tmpExtractDir);
                    return [
                        'success' => false,
                        'message' => 'Update archive structure invalid (missing module.json).',
                    ];
                }

                File::copyDirectory($sourceDir, $modulePath);
                File::deleteDirectory($tmpExtractDir);

                if (class_exists(SchemaHelper::class)) {
                    SchemaHelper::ensureRouteRequestsColumns(true);
                }

                try {
                    Artisan::call('migrate', ['--force' => true]);
                } catch (\Throwable $e) {}

                try {
                    Cache::forget(self::CACHE_KEY . '_stable');
                    Cache::forget(self::CACHE_KEY . '_prerelease');
                    Artisan::call('optimize:clear');
                } catch (\Throwable $e) {}

                return [
                    'success' => true,
                    'message' => 'Successfully updated RouteRequest to v' . ($info['latest_version'] ?? 'latest') . '! Caches and database schema have been refreshed.',
                ];
            } catch (\Throwable $e) {
                @unlink($tmpZip);
                if (is_dir($tmpExtractDir)) {
                    File::deleteDirectory($tmpExtractDir);
                }
                Log::error('RouteRequest 1-Click Update Error: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => '1-Click Update encountered an error: ' . $e->getMessage(),
                ];
            }
        }

        /**
         * Evaluates installed PaxStudio version against the Master DRM Server's latest version
         * and minimum compatibility floor (default v2.22.0). Supports both minor & major updates.
         */
        public static function checkPaxStudio(array $entitlements): array
        {
            $installed = !empty($entitlements['paxstudio_installed']);
            $currentVer = DrmEntitlements::getPaxStudioInstalledVersion();
            $srvInfo = is_array($entitlements['paxstudio_update'] ?? null) ? $entitlements['paxstudio_update'] : [];

            $minVer = ltrim(trim((string) ($srvInfo['min_version'] ?? '2.22.0')), 'vV') ?: '2.22.0';
            $latestVer = ltrim(trim((string) ($srvInfo['latest_version'] ?? '2.25.1')), 'vV') ?: '2.25.1';
            $hasAutoZip = !empty($srvInfo['has_auto_zip']);

            $tooOld = ($installed && $currentVer !== null && version_compare($currentVer, $minVer, '<'));
            $updateAvailable = ($installed && $currentVer !== null && version_compare($latestVer, $currentVer, '>'));

            $updateType = 'minor';
            if ($updateAvailable && $currentVer !== null) {
                $curMajor = (int) explode('.', $currentVer)[0];
                $latMajor = (int) explode('.', $latestVer)[0];
                if ($latMajor > $curMajor) {
                    $updateType = 'major';
                }
            }

            return [
                'installed'        => $installed,
                'current_version'  => $currentVer,
                'min_version'      => $minVer,
                'latest_version'   => $latestVer,
                'too_old'          => $tooOld,
                'update_available' => $updateAvailable,
                'update_type'      => $updateType,
                'has_auto_zip'     => $hasAutoZip,
                'release_name'     => (string) ($srvInfo['release_name'] ?? ''),
                'published_at'     => (string) ($srvInfo['published_at'] ?? ''),
                'changelog'        => (string) ($srvInfo['changelog'] ?? ''),
            ];
        }

        /**
         * 1-Click Automatic Minor & Major Updater for PaxStudio via the Master DRM Server proxy.
         */
        public static function installPaxStudioUpdate(): array
        {
            if (!class_exists(\ZipArchive::class)) {
                return ['success' => false, 'message' => 'PHP ZipArchive extension is required for 1-Click PaxStudio updates.'];
            }

            $key = DrmEntitlements::getLicenseKey();
            $endpoint = DrmEntitlements::getBaseApi() . '/paxstudio/download';

            try {
                $response = Http::timeout(45)->withHeaders([
                    'X-RouteRequest-License' => $key,
                    'X-RouteRequest-Domain'  => url(''),
                ])->get($endpoint);

                if (!$response->successful()) {
                    $err = $response->json('error') ?? ('HTTP ' . $response->status());
                    return ['success' => false, 'message' => 'Could not download PaxStudio update from Master Server: ' . $err];
                }

                $tmpZip = storage_path('app/paxstudio_update_' . time() . '.zip');
                file_put_contents($tmpZip, $response->body());

                $zip = new \ZipArchive();
                if ($zip->open($tmpZip) !== true) {
                    @unlink($tmpZip);
                    return ['success' => false, 'message' => 'Downloaded PaxStudio archive is not a valid ZIP file.'];
                }

                self::assertSafeZipArchive($zip);

                $tmpExtractDir = storage_path('app/paxstudio_extract_' . time());
                File::makeDirectory($tmpExtractDir, 0755, true, true);
                $zip->extractTo($tmpExtractDir);
                $zip->close();
                @unlink($tmpZip);

                $sourceDir = $tmpExtractDir;
                if (is_dir($tmpExtractDir . '/PaxStudio') && file_exists($tmpExtractDir . '/PaxStudio/module.json')) {
                    $sourceDir = $tmpExtractDir . '/PaxStudio';
                } else {
                    $subdirs = File::directories($tmpExtractDir);
                    if (count($subdirs) === 1 && file_exists($subdirs[0] . '/module.json')) {
                        $sourceDir = $subdirs[0];
                    }
                }

                $targetDir = base_path('modules' . DIRECTORY_SEPARATOR . 'PaxStudio');
                if (!is_dir($targetDir)) {
                    File::makeDirectory($targetDir, 0755, true, true);
                }

                File::copyDirectory($sourceDir, $targetDir);
                File::deleteDirectory($tmpExtractDir);

                try {
                    Artisan::call('optimize:clear');
                } catch (\Throwable $e) {}

                DrmEntitlements::current(true);
                $newVer = DrmEntitlements::getPaxStudioInstalledVersion() ?? 'latest';

                return [
                    'success' => true,
                    'message' => 'PaxStudio updated automatically to v' . $newVer . ' and application cache cleared!',
                ];
            } catch (\Throwable $e) {
                return ['success' => false, 'message' => 'PaxStudio 1-Click Update failed: ' . $e->getMessage()];
            }
        }
    }
}
