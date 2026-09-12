<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_profile_id')->constrained()->cascadeOnDelete();
            $table->string('player_name');
            $table->string('shirt_name')->nullable();
            $table->unsignedSmallInteger('shirt_number')->nullable();
            $table->string('shirt_size', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('team_members'); }
};
