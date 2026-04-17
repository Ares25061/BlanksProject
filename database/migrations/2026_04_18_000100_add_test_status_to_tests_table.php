<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->string('test_status', 20)->default('active')->after('is_active');
        });

        DB::table('tests')
            ->whereNull('deleted_at')
            ->update([
                'test_status' => DB::raw("CASE WHEN is_active = 1 THEN 'active' ELSE 'draft' END"),
            ]);
    }

    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropColumn('test_status');
        });
    }
};
