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
        Schema::create('ai_action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('conversation_id')
                ->nullable()
                ->constrained('ai_conversations')
                ->nullOnDelete();

            $table->foreignId('message_id')
                ->nullable()
                ->constrained('ai_messages')
                ->nullOnDelete();

            $table->string('action');

            $table->json('parameters');

            $table->enum('status', [
                'pending',
                'confirmed',
                'executed',
                'cancelled',
                'failed'
            ])->default('pending');

            $table->timestamp('confirmed_at')->nullable();

            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_action_logs');
    }
};
