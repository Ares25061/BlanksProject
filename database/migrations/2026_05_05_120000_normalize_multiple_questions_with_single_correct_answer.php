<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('questions')
            ->select('questions.id')
            ->leftJoin('answers', 'answers.question_id', '=', 'questions.id')
            ->where('questions.type', 'multiple')
            ->groupBy('questions.id')
            ->havingRaw('SUM(CASE WHEN answers.is_correct = 1 THEN 1 ELSE 0 END) <= 1')
            ->orderBy('questions.id')
            ->pluck('questions.id')
            ->chunk(500)
            ->each(function ($questionIds) {
                DB::table('questions')
                    ->whereIn('id', $questionIds->all())
                    ->update([
                        'type' => 'single',
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        //
    }
};
