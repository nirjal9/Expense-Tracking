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
        Schema::create('merchant_category_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('merchant')->index();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->decimal('confidence', 3, 2)->default(0.8);
            $table->integer('usage_count')->default(1);
            $table->timestamp('last_used')->nullable();
            $table->timestamps();
            
            $table->unique('merchant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_category_mappings');
    }
};
