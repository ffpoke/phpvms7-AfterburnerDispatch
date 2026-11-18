<?php

namespace Modules\RouteRequest\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (!class_exists(SchemaHelper::class, false)) {
    class SchemaHelper
    {
        public const SCHEMA_CACHE_KEY = 'routerequest_schema_v2_0_0_verified';

        /**
         * Guarantees all v2.0.0 columns exist on `route_requests`.
         * Caches the verification flag so zero schema queries run on normal page loads.
         */
        public static function ensureRouteRequestsColumns(bool $force = false): void
        {
            if (!$force) {
                try {
                    if (Cache::get(self::SCHEMA_CACHE_KEY) === true) {
                        return;
                    }
                } catch (\Throwable $e) {
                    // Continue if cache driver unavailable
                }
            }

            try {
                if (!Schema::hasTable('route_requests')) {
                    return;
                }
            } catch (\Throwable $e) {
                return;
            }

            $allPresent = true;

            // 1. typerating_id
            try {
                if (!Schema::hasColumn('route_requests', 'typerating_id')) {
                    Schema::table('route_requests', function (Blueprint $table) {
                        $table->unsignedInteger('typerating_id')->nullable()->index();
                    });
                }
            } catch (\Throwable $e) {
                try {
                    DB::statement('ALTER TABLE `route_requests` ADD COLUMN `typerating_id` INT UNSIGNED NULL');
                } catch (\Throwable $e2) {
                    $allPresent = false;
                }
            }

            // 2. flight_id
            try {
                if (!Schema::hasColumn('route_requests', 'flight_id')) {
                    Schema::table('route_requests', function (Blueprint $table) {
                        $table->string('flight_id', 36)->nullable()->index();
                    });
                }
            } catch (\Throwable $e) {
                try {
                    DB::statement('ALTER TABLE `route_requests` ADD COLUMN `flight_id` VARCHAR(36) NULL');
                } catch (\Throwable $e2) {
                    $allPresent = false;
                }
            }

            // 3. generated_flights
            try {
                if (!Schema::hasColumn('route_requests', 'generated_flights')) {
                    Schema::table('route_requests', function (Blueprint $table) {
                        $table->text('generated_flights')->nullable();
                    });
                }
            } catch (\Throwable $e) {
                try {
                    DB::statement('ALTER TABLE `route_requests` ADD COLUMN `generated_flights` TEXT NULL');
                } catch (\Throwable $e2) {
                    $allPresent = false;
                }
            }

            // 4. multiple_destinations
            try {
                if (!Schema::hasColumn('route_requests', 'multiple_destinations')) {
                    Schema::table('route_requests', function (Blueprint $table) {
                        $table->boolean('multiple_destinations')->default(false);
                    });
                }
            } catch (\Throwable $e) {
                try {
                    DB::statement('ALTER TABLE `route_requests` ADD COLUMN `multiple_destinations` TINYINT(1) NOT NULL DEFAULT 0');
                } catch (\Throwable $e2) {
                    $allPresent = false;
                }
            }

            // 5. request_return
            try {
                if (!Schema::hasColumn('route_requests', 'request_return')) {
                    Schema::table('route_requests', function (Blueprint $table) {
                        $table->boolean('request_return')->default(false);
                    });
                }
            } catch (\Throwable $e) {
                try {
                    DB::statement('ALTER TABLE `route_requests` ADD COLUMN `request_return` TINYINT(1) NOT NULL DEFAULT 0');
                } catch (\Throwable $e2) {
                    $allPresent = false;
                }
            }

            if ($allPresent) {
                try {
                    Cache::forever(self::SCHEMA_CACHE_KEY, true);
                } catch (\Throwable $e) {}
            }
        }

        /**
         * Compute a fast fingerprint hash of the phpVMS `airports` table
         * so browsers automatically refresh their local IndexedDB cache whenever
         * a new module version is installed OR an airport is added/updated/deleted in phpVMS!
         */
        public static function getAirportsCacheHash(): string
        {
            try {
                $row = DB::table('airports')
                    ->selectRaw('COUNT(*) as cnt, MAX(updated_at) as max_upd')
                    ->first();
                $cnt = $row->cnt ?? 0;
                $upd = $row->max_upd ?? '0';
                return md5('v2.0.0_afterburner_' . $cnt . '_' . $upd);
            } catch (\Throwable $e) {
                return 'v2.0.0_afterburner_default';
            }
        }
    }
}
