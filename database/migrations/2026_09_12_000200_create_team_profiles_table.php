<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('team_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('team_name');
            $table->string('logo')->nullable();
            $table->string('captain')->nullable();
            $table->string('contact')->nullable();
            $table->string('phone', 25)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'team_name']);
        });
    }

    public function down(): void { Schema::dropIfExists('team_profiles'); }
};
