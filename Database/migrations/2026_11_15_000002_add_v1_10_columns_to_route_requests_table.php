<?php

declare(strict_types=1);

use App\Contracts\Migration;
use Modules\AfterburnerDispatch\Support\SchemaHelper;

return new class() extends Migration {
    public function up(): void
    {
        if (!class_exists(SchemaHelper::class, false)) {
            require_once __DIR__ . '/../../Support/SchemaHelper.php';
        }
        SchemaHelper::ensureRouteRequestsColumns(true);
    }

    public function down(): void
    {
        // Preserve column data
    }
};
