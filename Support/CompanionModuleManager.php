<?php

declare(strict_types=1);

namespace Modules\AfterburnerDispatch\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use ZipArchive;

if (!class_exists(CompanionModuleManager::class, false)) {
    class CompanionModuleManager
    {
        public const array MODULES = [
            'DisposableBasic' => [
                'name'          => 'DisposableBasic',
                'folder'        => 'DisposableBasic',
                'repo'          => 'FatihKoz/DisposableBasic',
                'default_branch'=> 'main',
                'private_repo'  => false,
                'purpose'       => 'Required for Pilot JumpSeat & Aircraft Transfer pricing rules. Configure all travel/transfer rates inside DisposableBasic so other modules remain intact.',
                'config_hint'   => 'Admin → Addons → Disposable Basic',
            ],
            'ManualPirepControl' => [
                'name'          => 'ManualPirepControl',
                'folder'        => 'ManualPirepControl',
                'repo'          => 'parkho/ManualPirepControl',
                'default_branch'=> 'main',
                'private_repo'  => false,
                'purpose'       => 'Controls whether pilots can file Manual PIREPs. When disabled, AfterburnerDispatch Flagship + PaxStudio activates guided Pilot/Aircraft travel checks.',
                'config_hint'   => 'Admin → Settings → PIREPs (Allow Manual PIREP Filing)',
            ],
            'PaxStudio' => [
                'name'          => 'PaxStudio',
                'folder'        => 'PaxStudio',
                'repo'          => 'MANFahrer-GF/PaxStudio',
                'default_branch'=> 'main',
                'private_repo'  => true,
                'releases_url'  => 'https://github.com/MANFahrer-GF/PaxStudio/releases',
                'purpose'       => 'Professional Dispatch, Loadsheet & Smart Cargo Studio (private GitHub repository by MANFahrer-GF). AfterburnerDispatch bridges PaxStudio loadsheets, fares, and Guided Travel automatically.',
                'config_hint'   => 'Admin → Addons → PaxStudio',
            ],
            'SkyAdventures' => [
                'name'          => 'SkyAdventures',
                'folder'        => 'SkyAdventures',
                'repo'          => 'MANFahrer-GF/SkyAdventures',
                'default_branch'=> 'main',
                'private_repo'  => true,
                'releases_url'  => 'https://github.com/MANFahrer-GF/SkyAdventures/releases',
                'purpose'       => 'Tours, Events, Fly-Ins & Patches (private GitHub repository by MANFahrer-GF). AfterburnerDispatch bridges active Tour legs, Scenery lookup, and Ferry lock protection automatically.',
                'config_hint'   => 'Admin → SkyAdventures',
            ],
        ];

        public static function isModuleInstalled(string $folder): bool
        {
            return is_dir(base_path('modules/' . $folder))
                && file_exists(base_path('modules/' . $folder . '/module.json'));
        }

        public static function isModuleEnabled(string $folder): bool
        {
            if (!self::isModuleInstalled($folder)) {
                return false;
            }
            $statusFile = base_path('modules_statuses.json');
            if (!file_exists($statusFile)) {
                return true;
            }
            try {
                $raw = file_get_contents($statusFile);
                if ($raw === false || !json_validate($raw)) {
                    return true;
                }
                $map = json_decode($raw, true);
                if (!is_array($map)) {
                    return true;
                }
                return !empty($map[$folder]);
            } catch (\Throwable $e) {
                return true;
            }
        }

        public static function getInstalledVersion(string $folder): ?string
        {
            $jsonPath = base_path('modules/' . $folder . '/module.json');
            if (!file_exists($jsonPath)) {
                return null;
            }
            try {
                $raw = file_get_contents($jsonPath);
                if ($raw === false || !json_validate($raw)) {
                    return null;
                }
                $data = json_decode($raw, true);
                $ver = is_array($data) ? trim((string) ($data['version'] ?? '')) : '';
                return $ver !== '' ? ltrim($ver, 'vV') : 'installed';
            } catch (\Throwable $e) {
                return null;
            }
        }

        /**
         * Checks whether Manual PIREP filing is currently disabled via ManualPirepControl.
         * ManualPirepControl is fail-closed (disabled by default once installed & enabled).
         */
        public static function isManualPirepDisabled(): bool
        {
            if (!self::isModuleEnabled('ManualPirepControl')) {
                return false;
            }

            try {
                if (Schema::hasTable('settings')) {
                    // Check common keys used by ManualPirepControl in phpVMS settings table
                    $row = DB::table('settings')
                        ->where(function ($q) {
                            $q->where('key', 'pireps.allow_manual_pirep')
                              ->orWhere('key', 'pireps.allow_manual')
                              ->orWhere('key', 'pireps_allow_manual_pirep')
                              ->orWhere('key', 'pireps_allow_manual')
                              ->orWhere('key', 'like', '%manual_pirep%');
                        })
                        ->first();

                    if ($row) {
                        $val = strtolower(trim((string) ($row->value ?? '0')));
                        $allowed = in_array($val, ['1', 'true', 'yes', 'on'], true);
                        return !$allowed;
                    }
                }
            } catch (\Throwable $e) {}

            // ManualPirepControl is fail-closed (disabled by default)
            return true;
        }

        /**
         * Fetches latest release info from GitHub for a companion module (cached 6h).
         */
        public static function getLatestReleaseInfo(string $moduleKey, bool $forceRefresh = false): array
        {
            $meta = self::MODULES[$moduleKey] ?? null;
            if (!$meta) {
                return ['latest_version' => null, 'download_url' => null, 'html_url' => null, 'private_repo' => false];
            }
            $isPrivate = !empty($meta['private_repo']);
            $pat = ClientSettings::getGitHubPat();
            $releasesUrl = (string) ($meta['releases_url'] ?? ("https://github.com/{$meta['repo']}/releases"));
            if ($isPrivate && $pat === '') {
                return [
                    'latest_version' => null,
                    'download_url'   => null,
                    'html_url'       => $releasesUrl,
                    'private_repo'   => true,
                    'pat_configured' => false,
                    'use_api_asset'  => false,
                ];
            }

            $cacheKey = 'rr_companion_gh_' . strtolower($moduleKey);
            if ($forceRefresh) {
                Cache::forget($cacheKey);
            }

            return Cache::remember($cacheKey, 21600, function () use ($meta, $isPrivate, $pat, $releasesUrl): array {
                $repo = $meta['repo'];
                $fallbackZip = $isPrivate
                    ? "https://api.github.com/repos/{$repo}/zipball/{$meta['default_branch']}"
                    : "https://github.com/{$repo}/archive/refs/heads/{$meta['default_branch']}.zip";
                $htmlUrl = $isPrivate ? $releasesUrl : "https://github.com/{$repo}";

                try {
                    $headers = [
                        'User-Agent'           => 'phpVMS7-AfterburnerDispatch-CompanionUpdater/4.1.3',
                        'Accept'               => 'application/vnd.github+json',
                        'X-GitHub-Api-Version' => '2022-11-28',
                    ];
                    if ($pat !== '') {
                        $headers['Authorization'] = 'Bearer ' . $pat;
                    }
                    $res = Http::withHeaders($headers)->timeout(8)->get("https://api.github.com/repos/{$repo}/releases/latest");

                    if ($res->successful() && is_array($res->json())) {
                        $rel = $res->json();
                        $tag = ltrim((string) ($rel['tag_name'] ?? ''), 'vV');
                        $dlUrl = (string) ($rel['zipball_url'] ?? $fallbackZip);
                        $useApiAsset = false;
                        foreach (($rel['assets'] ?? []) as $asset) {
                            $aName = (string) ($asset['name'] ?? '');
                            if (str_ends_with(strtolower($aName), '.zip')) {
                                if ($isPrivate && !empty($asset['url'])) {
                                    $dlUrl = (string) $asset['url'];
                                    $useApiAsset = true;
                                    break;
                                } elseif (!empty($asset['browser_download_url'])) {
                                    $dlUrl = (string) $asset['browser_download_url'];
                                    break;
                                }
                            }
                        }
                        return [
                            'latest_version' => $tag !== '' ? $tag : null,
                            'download_url'   => $dlUrl,
                            'html_url'       => (string) ($rel['html_url'] ?? $htmlUrl),
                            'private_repo'   => $isPrivate,
                            'pat_configured' => $pat !== '',
                            'use_api_asset'  => $useApiAsset,
                        ];
                    }
                } catch (\Throwable $e) {}

                return [
                    'latest_version' => null,
                    'download_url'   => $fallbackZip,
                    'html_url'       => $htmlUrl,
                    'private_repo'   => $isPrivate,
                    'pat_configured' => $pat !== '',
                    'use_api_asset'  => false,
                ];
            });
        }

        /**
         * Returns full status array for DisposableBasic and ManualPirepControl for Admin UI display.
         */
        public static function getStatusSummary(bool $forceRefresh = false): array
        {
            $out = [];
            foreach (self::MODULES as $key => $meta) {
                $installed = self::isModuleInstalled($meta['folder']);
                $enabled = self::isModuleEnabled($meta['folder']);
                $currentVer = self::getInstalledVersion($meta['folder']);
                $gh = self::getLatestReleaseInfo($key, $forceRefresh);
                $latestVer = $gh['latest_version'];

                $updateAvailable = false;
                if ($installed && $currentVer && $latestVer && $currentVer !== 'installed') {
                    $updateAvailable = version_compare($latestVer, $currentVer, '>');
                }

                $out[$key] = [
                    'key'              => $key,
                    'name'             => $meta['name'],
                    'repo'             => $meta['repo'],
                    'purpose'          => $meta['purpose'],
                    'config_hint'      => $meta['config_hint'],
                    'installed'        => $installed,
                    'enabled'          => $enabled,
                    'current_version'  => $currentVer,
                    'latest_version'   => $latestVer,
                    'update_available' => $updateAvailable,
                    'download_url'     => $gh['download_url'],
                    'html_url'         => $gh['html_url'],
                    'private_repo'     => !empty($meta['private_repo']),
                    'pat_configured'   => ClientSettings::hasGitHubPat(),
                    'releases_url'     => (string) ($meta['releases_url'] ?? $gh['html_url']),
                ];
            }

            return $out;
        }

        /**
         * 1-Click Install or Update DisposableBasic or ManualPirepControl using Zip-Slip validation.
         */
        public static function installOrUpdate(string $moduleKey): array
        {
            $meta = self::MODULES[$moduleKey] ?? null;
            if (!$meta) {
                return ['success' => false, 'message' => 'Unknown companion module requested.'];
            }
            $isPrivate = !empty($meta['private_repo']);
            $pat = ClientSettings::getGitHubPat();
            if ($isPrivate && $pat === '') {
                $releasesUrl = (string) ($meta['releases_url'] ?? ("https://github.com/{$meta['repo']}/releases"));
                return [
                    'success' => false,
                    'message' => "{$meta['name']} is hosted in a private GitHub repository. Configure your own GitHub Personal Access Token (classic, repo scope) in the Companion Modules panel above to enable 1-click updates, or download manually from {$releasesUrl}.",
                ];
            }

            if (!class_exists(ZipArchive::class)) {
                return ['success' => false, 'message' => 'PHP ZipArchive extension is required for 1-click module installation.'];
            }

            $gh = self::getLatestReleaseInfo($moduleKey, true);
            $dlUrl = (string) ($gh['download_url'] ?? '');
            if ($dlUrl === '') {
                return ['success' => false, 'message' => "Could not resolve download URL for {$meta['name']}."];
            }

            $tmpZip = storage_path('app/rr_companion_' . strtolower($moduleKey) . '_' . time() . '.zip');
            $tmpExtract = storage_path('app/rr_companion_extract_' . strtolower($moduleKey) . '_' . time());

            try {
                $dlHeaders = [
                    'User-Agent'           => 'phpVMS7-AfterburnerDispatch-CompanionUpdater/4.1.3',
                    'Accept'               => !empty($gh['use_api_asset']) ? 'application/octet-stream' : 'application/octet-stream, application/zip, */*',
                    'X-GitHub-Api-Version' => '2022-11-28',
                ];
                if ($pat !== '') {
                    $dlHeaders['Authorization'] = 'Bearer ' . $pat;
                }
                $res = Http::withHeaders($dlHeaders)->timeout(45)->get($dlUrl);

                if (!$res->successful()) {
                    return ['success' => false, 'message' => "Failed to download {$meta['name']} (HTTP {$res->status()})."];
                }

                File::put($tmpZip, $res->body());

                $zip = new ZipArchive();
                if ($zip->open($tmpZip) !== true) {
                    @unlink($tmpZip);
                    return ['success' => false, 'message' => "Downloaded {$meta['name']} archive is not a valid ZIP file."];
                }

                UpdateManager::assertSafeZipArchive($zip);
                File::makeDirectory($tmpExtract, 0755, true, true);
                $zip->extractTo($tmpExtract);
                $zip->close();
                @unlink($tmpZip);

                // Locate folder containing module.json
                $sourceDir = null;
                if (file_exists($tmpExtract . '/module.json')) {
                    $sourceDir = $tmpExtract;
                } elseif (is_dir($tmpExtract . '/' . $meta['folder']) && file_exists($tmpExtract . '/' . $meta['folder'] . '/module.json')) {
                    $sourceDir = $tmpExtract . '/' . $meta['folder'];
                } else {
                    foreach (File::directories($tmpExtract) as $sub) {
                        if (file_exists($sub . '/module.json')) {
                            $sourceDir = $sub;
                            break;
                        }
                        if (is_dir($sub . '/' . $meta['folder']) && file_exists($sub . '/' . $meta['folder'] . '/module.json')) {
                            $sourceDir = $sub . '/' . $meta['folder'];
                            break;
                        }
                    }
                }

                if (!$sourceDir || !file_exists($sourceDir . '/module.json')) {
                    File::deleteDirectory($tmpExtract);
                    return ['success' => false, 'message' => "Invalid archive structure for {$meta['name']} (missing module.json)."];
                }

                $targetDir = base_path('modules/' . $meta['folder']);
                if (!is_dir($targetDir)) {
                    File::makeDirectory($targetDir, 0755, true, true);
                }

                File::copyDirectory($sourceDir, $targetDir);
                File::deleteDirectory($tmpExtract);

                try {
                    Artisan::call('module:enable', ['module' => $meta['folder']]);
                } catch (\Throwable $e) {}

                try {
                    Artisan::call('module:migrate', ['module' => $meta['folder'], '--force' => true]);
                } catch (\Throwable $e) {
                    try {
                        Artisan::call('migrate', ['--force' => true]);
                    } catch (\Throwable $e2) {}
                }

                try {
                    Artisan::call('optimize:clear');
                } catch (\Throwable $e) {}

                Cache::forget('rr_companion_gh_' . strtolower($moduleKey));

                return [
                    'success' => true,
                    'message' => "{$meta['name']} installed/updated cleanly! Important: Please configure its settings directly in {$meta['config_hint']} so other modules remain intact.",
                ];
            } catch (\Throwable $e) {
                @unlink($tmpZip);
                if (is_dir($tmpExtract)) {
                    File::deleteDirectory($tmpExtract);
                }
                Log::error("AfterburnerDispatch Companion Install Error ({$moduleKey}): " . $e->getMessage());
                return ['success' => false, 'message' => "Failed to install/update {$meta['name']}: " . $e->getMessage()];
            }
        }
    }
}
