<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('refund_reference', 100)->nullable()->after('refunded_at');
            $table->foreignId('refund_confirmed_by')->nullable()->after('refund_reference')->constrained('users')->nullOnDelete();
        });

        Schema::table('admin_notifications', function (Blueprint $table): void {
            $table->string('event_key', 100)->nullable()->unique()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('admin_notifications', function (Blueprint $table): void {
            $table->dropUnique(['event_key']);
            $table->dropColumn('event_key');
        });
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['refund_confirmed_by']);
            $table->dropColumn(['refund_reference', 'refund_confirmed_by']);
        });
    }
};
