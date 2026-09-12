<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customization_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30)->default('design_pending')->index();
            $table->string('print_name')->nullable();
            $table->string('shirt_number')->nullable();
            $table->string('font')->nullable();
            $table->string('style')->nullable();
            $table->string('print_color')->nullable();
            $table->text('notes')->nullable();
            $table->string('artwork_path')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'order_item_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('customization_jobs'); }
};
