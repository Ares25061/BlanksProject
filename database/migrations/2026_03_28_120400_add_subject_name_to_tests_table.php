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
            $table->string('subject_name')->nullable()->after('title');
        });

        DB::table('tests')
            ->whereNull('subject_name')
            ->update([
                'subject_name' => DB::raw('title'),
            ]);
    }

    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropColumn('subject_name');
        });
    }
};
