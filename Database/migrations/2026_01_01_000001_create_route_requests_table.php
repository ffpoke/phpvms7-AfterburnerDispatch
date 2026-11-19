<?php

declare(strict_types=1);

use App\Contracts\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\RouteRequest\Support\SchemaHelper;

return new class() extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('route_requests')) {
            Schema::create('route_requests', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id')->index();
                $table->unsignedInteger('typerating_id')->nullable()->index();
                $table->string('flight_id', 36)->nullable()->index();
                $table->text('generated_flights')->nullable();
                $table->boolean('multiple_destinations')->default(false);
                $table->boolean('request_return')->default(false);
                $table->string('aircraft_type', 120);
                $table->string('realworld_airline', 150);
                $table->string('realworld_flight_number', 30)->nullable();
                $table->string('dep_airport_id', 5)->index();
                $table->string('arr_airport_id', 5)->nullable()->index();
                $table->string('recipient_email', 191)->default('admin@flyfantasyair.net');
                $table->text('notes')->nullable();
                $table->string('status', 30)->default('pending');
                $table->text('admin_notes')->nullable();
                $table->timestamps();
            });
        } else {
            if (!class_exists(SchemaHelper::class, false)) {
                require_once __DIR__ . '/../../Support/SchemaHelper.php';
            }
            SchemaHelper::ensureRouteRequestsColumns(true);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('route_requests');
    }
};
