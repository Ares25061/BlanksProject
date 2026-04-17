<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('electronic_test_logs');
        Schema::dropIfExists('electronic_test_answers');
        Schema::dropIfExists('electronic_test_attempts');
        Schema::dropIfExists('electronic_test_session_members');
        Schema::dropIfExists('electronic_test_sessions');

        Schema::create('electronic_test_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_id');
            $table->unsignedBigInteger('student_group_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->string('access_token', 80);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->unique('access_token', 'ets_access_token_uq');
            $table->foreign('test_id', 'ets_test_fk')->references('id')->on('tests')->cascadeOnDelete();
            $table->foreign('student_group_id', 'ets_group_fk')->references('id')->on('student_groups')->nullOnDelete();
            $table->foreign('created_by', 'ets_creator_fk')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('electronic_test_session_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('electronic_test_session_id');
            $table->unsignedBigInteger('group_student_id');
            $table->unsignedTinyInteger('variant_number')->default(1);
            $table->string('access_token', 80);
            $table->timestamps();

            $table->unique('access_token', 'etsm_access_token_uq');
            $table->unique(['electronic_test_session_id', 'group_student_id'], 'etsm_session_student_uq');
            $table->foreign('electronic_test_session_id', 'etsm_session_fk')
                ->references('id')
                ->on('electronic_test_sessions')
                ->cascadeOnDelete();
            $table->foreign('group_student_id', 'etsm_student_fk')
                ->references('id')
                ->on('group_students')
                ->cascadeOnDelete();
        });

        Schema::create('electronic_test_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('electronic_test_session_id');
            $table->unsignedBigInteger('electronic_test_session_member_id')->nullable();
            $table->unsignedBigInteger('test_id');
            $table->unsignedBigInteger('student_group_id')->nullable();
            $table->unsignedBigInteger('group_student_id')->nullable();
            $table->unsignedBigInteger('assigned_grade_by')->nullable();
            $table->unsignedTinyInteger('variant_number')->default(1);
            $table->string('access_token', 80);
            $table->string('access_type', 30)->default('session_link');
            $table->string('student_full_name');
            $table->boolean('is_manual_student')->default(false);
            $table->string('status', 30)->default('in_progress');
            $table->integer('total_score')->nullable();
            $table->string('grade_label')->nullable();
            $table->string('assigned_grade_value', 50)->nullable();
            $table->date('assigned_grade_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique('access_token', 'eta_access_token_uq');
            $table->index(['electronic_test_session_id', 'status'], 'eta_session_status_idx');
            $table->index(['test_id', 'submitted_at'], 'eta_test_submitted_idx');
            $table->foreign('electronic_test_session_id', 'eta_session_fk')
                ->references('id')
                ->on('electronic_test_sessions')
                ->cascadeOnDelete();
            $table->foreign('electronic_test_session_member_id', 'eta_member_fk')
                ->references('id')
                ->on('electronic_test_session_members')
                ->nullOnDelete();
            $table->foreign('test_id', 'eta_test_fk')
                ->references('id')
                ->on('tests')
                ->cascadeOnDelete();
            $table->foreign('student_group_id', 'eta_group_fk')
                ->references('id')
                ->on('student_groups')
                ->nullOnDelete();
            $table->foreign('group_student_id', 'eta_student_fk')
                ->references('id')
                ->on('group_students')
                ->nullOnDelete();
            $table->foreign('assigned_grade_by', 'eta_assigned_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::create('electronic_test_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('electronic_test_attempt_id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('answer_id')->nullable();
            $table->json('selected_answers')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->integer('points_earned')->nullable();
            $table->timestamps();

            $table->unique(['electronic_test_attempt_id', 'question_id'], 'eta_answer_attempt_question_uq');
            $table->foreign('electronic_test_attempt_id', 'eta_answer_attempt_fk')
                ->references('id')
                ->on('electronic_test_attempts')
                ->cascadeOnDelete();
            $table->foreign('question_id', 'eta_answer_question_fk')
                ->references('id')
                ->on('questions')
                ->cascadeOnDelete();
            $table->foreign('answer_id', 'eta_answer_option_fk')
                ->references('id')
                ->on('answers')
                ->nullOnDelete();
        });

        Schema::create('electronic_test_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('electronic_test_attempt_id');
            $table->string('event_type', 50);
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['electronic_test_attempt_id', 'occurred_at'], 'etl_attempt_time_idx');
            $table->foreign('electronic_test_attempt_id', 'etl_attempt_fk')
                ->references('id')
                ->on('electronic_test_attempts')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electronic_test_logs');
        Schema::dropIfExists('electronic_test_answers');
        Schema::dropIfExists('electronic_test_attempts');
        Schema::dropIfExists('electronic_test_session_members');
        Schema::dropIfExists('electronic_test_sessions');
    }
};
