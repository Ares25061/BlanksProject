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
            $table->string('delivery_mode', 20)->default('blank')->after('variant_count');
            $table->string('access_code', 20)->nullable()->after('delivery_mode');
        });

        DB::table('tests')->orderBy('id')->get()->each(function ($test) {
            DB::table('tests')
                ->where('id', $test->id)
                ->update([
                    'access_code' => $this->generateUniqueCode(),
                ]);
        });

        Schema::table('tests', function (Blueprint $table) {
            $table->unique('access_code');
        });
    }

    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropUnique(['access_code']);
            $table->dropColumn([
                'delivery_mode',
                'access_code',
            ]);
        });
    }

    private function generateUniqueCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = collect(range(1, 8))
                ->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])
                ->implode('');
        } while (DB::table('tests')->where('access_code', $code)->exists());

        return $code;
    }
};
