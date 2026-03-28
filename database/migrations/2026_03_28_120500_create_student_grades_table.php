<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_group_id')->constrained('student_groups')->cascadeOnDelete();
            $table->foreignId('group_student_id')->nullable()->constrained('group_students')->nullOnDelete();
            $table->foreignId('blank_form_id')->nullable()->constrained('blank_forms')->nullOnDelete();
            $table->string('subject_name');
            $table->string('grade_value', 50);
            $table->date('grade_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['group_student_id', 'subject_name', 'grade_date'], 'student_grades_cell_unique');
            $table->index(['student_group_id', 'subject_name', 'grade_date'], 'student_grades_group_subject_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_grades');
    }
};
