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
        Schema::table('expenses', function (Blueprint $table) {
            $table->boolean('is_auto_created')->default(false)->after('date');
            $table->string('source')->nullable()->after('is_auto_created'); // email, sms, webhook
            $table->string('notification_type')->nullable()->after('source'); // esewa, khalti, bank
            $table->string('transaction_id')->nullable()->after('notification_type');
            $table->string('merchant')->nullable()->after('transaction_id');
            $table->boolean('requires_approval')->default(false)->after('merchant');
            $table->timestamp('auto_created_at')->nullable()->after('requires_approval');
            $table->timestamp('approved_at')->nullable()->after('auto_created_at');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
            
            $table->index(['is_auto_created', 'requires_approval']);
            $table->index(['source', 'notification_type']);
            $table->index('transaction_id');
            $table->index('merchant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['is_auto_created', 'requires_approval']);
            $table->dropIndex(['source', 'notification_type']);
            $table->dropIndex(['transaction_id']);
            $table->dropIndex(['merchant']);
            
            $table->dropColumn([
                'is_auto_created',
                'source',
                'notification_type',
                'transaction_id',
                'merchant',
                'requires_approval',
                'auto_created_at',
                'approved_at',
                'rejected_at',
                'rejection_reason'
            ]);
        });
    }
};