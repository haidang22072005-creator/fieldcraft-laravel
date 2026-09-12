<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_order_drafts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('active')->index();
            $table->json('roster_snapshot');
            // NULL is deliberately used once a draft is no longer active. A
            // unique nullable key gives us one active draft per team while
            // retaining an unlimited history of cancelled drafts.
            $table->string('active_key', 64)->nullable()->unique();
            $table->timestamps();
            $table->index(['team_profile_id', 'status']);
        });

        Schema::create('team_order_draft_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_order_draft_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('player_name');
            $table->string('shirt_name')->nullable();
            $table->unsignedSmallInteger('shirt_number')->nullable();
            $table->string('shirt_size', 20)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->json('customization')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['team_order_draft_id', 'team_member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_order_draft_items');
        Schema::dropIfExists('team_order_drafts');
    }
};
