<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blank_forms', function (Blueprint $table) {
            $table->string('assigned_grade_value')->nullable()->after('grade_label');
            $table->date('assigned_grade_date')->nullable()->after('assigned_grade_value');
            $table->foreignId('assigned_grade_by')->nullable()->after('assigned_grade_date')->constrained('users')->nullOnDelete();
            $table->index(['group_student_id', 'assigned_grade_date'], 'blank_forms_student_grade_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('blank_forms', function (Blueprint $table) {
            $table->dropIndex('blank_forms_student_grade_date_idx');
            $table->dropConstrainedForeignId('assigned_grade_by');
            $table->dropColumn([
                'assigned_grade_value',
                'assigned_grade_date',
            ]);
        });
    }
};
