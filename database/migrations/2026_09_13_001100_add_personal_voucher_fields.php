<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->unsignedInteger('max_discount')->nullable()->after('value');
            $table->timestamp('starts_at')->nullable()->after('expires_at');
            $table->text('admin_note')->nullable()->after('starts_at');
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'is_active']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'max_discount', 'starts_at', 'admin_note']);
        });
    }
};
