<?php

declare(strict_types=1);

namespace Modules\AfterburnerDispatch\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (!class_exists(SchemaHelper::class, false)) {
    class SchemaHelper
    {
        public const string SCHEMA_CACHE_KEY = 'routerequest_schema_v4_0_0_verified';

        public static function ensureRouteRequestsColumns(bool $force = false): void
        {
            if (!$force) {
                try {
                    if (Cache::get(self::SCHEMA_CACHE_KEY) === true) {
                        return;
                    }
                } catch (\Throwable $e) {}
            }

            try {
                if (!Schema::hasTable('routerequest_client_settings')) {
                    Schema::create('routerequest_client_settings', function (Blueprint $table) {
                        $table->increments('id');
                        $table->string('setting_key', 100)->unique();
                        $table->text('setting_value')->nullable();
                        $table->timestamps();
                    });
                }
            } catch (\Throwable $e) {}

            try {
                if (!Schema::hasTable('routerequest_upvotes')) {
                    Schema::create('routerequest_upvotes', function (Blueprint $table) {
                        $table->increments('id');
                        $table->unsignedBigInteger('route_request_id')->index();
                        $table->unsignedBigInteger('user_id')->index();
                        $table->timestamp('created_at')->nullable();
                        $table->unique(['route_request_id', 'user_id'], 'rr_upvotes_req_user_unique');
                    });
                }
            } catch (\Throwable $e) {}

            try {
                if (!Schema::hasTable('route_requests')) {
                    return;
                }
            } catch (\Throwable $e) {
                return;
            }

            $allPresent = true;

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

        public static function getAirportsCacheHash(): string
        {
            try {
                $row = DB::table('airports')
                    ->selectRaw('COUNT(*) as cnt, MAX(updated_at) as max_upd')
                    ->first();
                $cnt = $row->cnt ?? 0;
                $upd = $row->max_upd ?? '0';
                return md5('v4.0.0_afterburner_' . $cnt . '_' . $upd);
            } catch (\Throwable $e) {
                return 'v4.0.0_afterburner_default';
            }
        }
    }
}
