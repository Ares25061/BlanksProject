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
            $table->unsignedTinyInteger('variant_count')->default(1)->after('grade_criteria');
        });

        Schema::table('blank_forms', function (Blueprint $table) {
            $table->unsignedTinyInteger('variant_number')->default(1)->after('form_number');
        });

        DB::table('tests')->whereNull('variant_count')->update(['variant_count' => 1]);
        DB::table('blank_forms')->whereNull('variant_number')->update(['variant_number' => 1]);
    }

    public function down(): void
    {
        Schema::table('blank_forms', function (Blueprint $table) {
            $table->dropColumn('variant_number');
        });

        Schema::table('tests', function (Blueprint $table) {
            $table->dropColumn('variant_count');
        });
    }
};
