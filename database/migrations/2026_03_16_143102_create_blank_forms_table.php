<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blank_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->onDelete('cascade');
            $table->string('form_number')->unique(); // уникальный номер бланка
            $table->string('last_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('group_name')->nullable();
            $table->date('submission_date')->nullable();
            $table->enum('status', ['generated', 'submitted', 'checked'])->default('generated');
            $table->integer('total_score')->nullable();
            $table->json('metadata')->nullable(); // дополнительная информация
            $table->foreignId('checked_by')->nullable()->constrained('users');
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blank_forms');
    }
};
