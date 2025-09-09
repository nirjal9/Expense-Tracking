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
        Schema::create('auto_created_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('source')->index(); // email, sms, webhook
            $table->string('notification_type')->index(); // esewa, khalti, bank
            $table->json('raw_data')->nullable(); // Original notification data
            $table->decimal('confidence_score', 3, 2)->default(0.0); // 0.00 to 1.00
            $table->enum('status', ['pending_approval', 'approved', 'rejected'])->default('pending_approval');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['source', 'notification_type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_created_expenses');
    }
};