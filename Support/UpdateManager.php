<?php

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
        public const CACHE_KEY = 'routerequest_github_update_v2_1';

        public static function getRepo(): string
        {
            return trim((string) config('routerequest.github_repo', 'ffpoke/phpvms7-RouteRequest')) ?: 'ffpoke/phpvms7-RouteRequest';
        }

        public static function getCurrentVersion(): string
        {
            return ltrim(trim((string) config('routerequest.version', '2.1.0')), 'vV');
        }

        /**
         * Check GitHub Releases API for `ffpoke/phpvms7-RouteRequest`.
         * Caches the result for 6 hours unless `$force = true`.
         */
        public static function check(bool $force = false): array
        {
            if ($force) {
                try {
                    Cache::forget(self::CACHE_KEY);
                } catch (\Throwable $e) {}
            }

            $currentVersion = self::getCurrentVersion();
            $repo = self::getRepo();

            $defaultData = [
                'checked'          => false,
                'repo'             => $repo,
                'current_version'  => $currentVersion,
                'latest_version'   => $currentVersion,
                'update_available' => false,
                'release_name'     => null,
                'changelog'        => null,
                'published_at'     => null,
                'download_url'     => null,
                'html_url'         => 'https://github.com/' . $repo . '/releases',
                'error'            => null,
            ];

            $fetchFn = function () use ($repo, $currentVersion, $defaultData) {
                try {
                    $url = 'https://api.github.com/repos/' . $repo . '/releases/latest';
                    $response = Http::withHeaders([
                        'User-Agent' => 'phpVMS7-RouteRequest-Updater/2.1.0 (ffpoke/phpvms7-RouteRequest)',
                        'Accept'     => 'application/vnd.github+json',
                    ])->timeout(8)->get($url);

                    if ($response->status() === 404) {
                        // Repository exists or is new with 0 published releases yet
                        $defaultData['checked'] = true;
                        return $defaultData;
                    }

                    if (!$response->successful()) {
                        $defaultData['error'] = 'GitHub API returned HTTP ' . $response->status();
                        return $defaultData;
                    }

                    $json = $response->json();
                    if (!is_array($json) || empty($json['tag_name'])) {
                        $defaultData['checked'] = true;
                        return $defaultData;
                    }

                    $latestTag = ltrim(trim((string) $json['tag_name']), 'vV');
                    $hasUpdate = version_compare($latestTag, $currentVersion, '>');

                    // Prefer an attached .zip release asset (e.g. RouteRequest-phpvms7-module-vX.Y.Z.zip)
                    $downloadUrl = $json['zipball_url'] ?? null;
                    if (!empty($json['assets']) && is_array($json['assets'])) {
                        foreach ($json['assets'] as $asset) {
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
                        'current_version'  => $currentVersion,
                        'latest_version'   => $latestTag,
                        'update_available' => $hasUpdate,
                        'release_name'     => (string) ($json['name'] ?? ('v' . $latestTag)),
                        'changelog'        => (string) ($json['body'] ?? ''),
                        'published_at'     => !empty($json['published_at']) ? date('d.M.Y H:i', strtotime($json['published_at'])) : null,
                        'download_url'     => $downloadUrl,
                        'html_url'         => (string) ($json['html_url'] ?? ('https://github.com/' . $repo . '/releases')),
                        'error'            => null,
                    ];
                } catch (\Throwable $e) {
                    $defaultData['error'] = $e->getMessage();
                    return $defaultData;
                }
            };

            try {
                return Cache::remember(self::CACHE_KEY, 21600, $fetchFn);
            } catch (\Throwable $e) {
                return $fetchFn();
            }
        }

        /**
         * 1-Click Self-Updater:
         * Downloads the latest release ZIP from GitHub, verifies integrity,
         * extracts over `/modules/RouteRequest`, runs migrations, and clears caches.
         */
        public static function installLatestUpdate(): array
        {
            $info = self::check(true);

            if (empty($info['download_url'])) {
                return [
                    'success' => false,
                    'message' => 'No downloadable release ZIP found on GitHub (' . self::getRepo() . ').',
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
                    'User-Agent' => 'phpVMS7-RouteRequest-Updater/2.1.0 (ffpoke/phpvms7-RouteRequest)',
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

                File::makeDirectory($tmpExtractDir, 0755, true, true);
                $zip->extractTo($tmpExtractDir);
                $zip->close();
                @unlink($tmpZip);

                // Determine whether the ZIP contains a top-level wrapper folder (e.g. RouteRequest/ or ffpoke-phpvms7-RouteRequest-xxxx/)
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

                // Copy updated module files over /modules/RouteRequest
                File::copyDirectory($sourceDir, $modulePath);
                File::deleteDirectory($tmpExtractDir);

                // Run SchemaHelper & clear application caches
                if (class_exists(SchemaHelper::class)) {
                    SchemaHelper::ensureRouteRequestsColumns(true);
                }

                try {
                    Artisan::call('migrate', ['--force' => true]);
                } catch (\Throwable $e) {}

                try {
                    Cache::forget(self::CACHE_KEY);
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
    }
}
