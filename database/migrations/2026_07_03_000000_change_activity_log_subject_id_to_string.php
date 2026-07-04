<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * spatie's default activity_log.subject_id is a bigint (nullableMorphs). The agency
 * models (Client, Project, Quote, …) use UUID string primary keys, which don't fit a
 * bigint. Widen subject_id to a string so UUID subjects can be logged. Existing
 * e-commerce subjects (integer ids) are unaffected — they're stored as their string form.
 */
return new class () extends Migration {
    public function up(): void
    {
        // Raw ALTER … USING because Postgres needs an explicit cast from bigint to varchar.
        DB::statement('ALTER TABLE activity_log ALTER COLUMN subject_id TYPE varchar(255) USING subject_id::varchar');
    }

    public function down(): void
    {
        // Reversible only when no non-numeric (UUID) subject ids exist.
        DB::statement('ALTER TABLE activity_log ALTER COLUMN subject_id TYPE bigint USING subject_id::bigint');
    }
};
