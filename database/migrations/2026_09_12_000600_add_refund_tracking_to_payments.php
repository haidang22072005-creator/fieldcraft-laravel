<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('refund_status', 20)->default('none')->after('status')->index();
            $table->text('refund_reason')->nullable()->after('refund_status');
            $table->timestamp('refunded_at')->nullable()->after('refund_reason');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['refund_status']);
            $table->dropColumn(['refund_status', 'refund_reason', 'refunded_at']);
        });
    }
};
