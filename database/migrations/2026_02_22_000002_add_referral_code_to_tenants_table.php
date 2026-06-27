<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('referral_code', 10)->nullable()->unique()->after('plan');
        });

        // Backfill existing tenants with unique codes
        $tenants = DB::table('tenants')->whereNull('referral_code')->get();

        foreach ($tenants as $tenant) {
            $code = $this->generateUniqueCode();
            DB::table('tenants')->where('id', $tenant->id)->update(['referral_code' => $code]);
        }
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('referral_code');
        });
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (DB::table('tenants')->where('referral_code', $code)->exists());

        return $code;
    }
};
