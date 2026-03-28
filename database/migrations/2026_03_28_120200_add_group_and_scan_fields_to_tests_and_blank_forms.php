<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->json('grade_criteria')->nullable()->after('is_active');
        });

        Schema::table('blank_forms', function (Blueprint $table) {
            $table->foreignId('student_group_id')->nullable()->after('test_id')->constrained('student_groups')->nullOnDelete();
            $table->foreignId('group_student_id')->nullable()->after('student_group_id')->constrained('group_students')->nullOnDelete();
            $table->string('patronymic')->nullable()->after('first_name');
            $table->string('grade_label')->nullable()->after('total_score');
            $table->string('scan_path')->nullable()->after('grade_label');
            $table->timestamp('scanned_at')->nullable()->after('scan_path');
        });
    }

    public function down(): void
    {
        Schema::table('blank_forms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_group_id');
            $table->dropConstrainedForeignId('group_student_id');
            $table->dropColumn([
                'patronymic',
                'grade_label',
                'scan_path',
                'scanned_at',
            ]);
        });

        Schema::table('tests', function (Blueprint $table) {
            $table->dropColumn('grade_criteria');
        });
    }
};
