<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->string('customization_name')->nullable()->after('quantity');
            $table->string('customization_number', 20)->nullable()->after('customization_name');
            $table->text('customization_notes')->nullable()->after('customization_number');
        });

        Schema::table('customization_jobs', function (Blueprint $table): void {
            $table->foreignId('customer_id')->nullable()->after('order_id')->constrained('users')->nullOnDelete();
            $table->string('customization_name')->nullable()->after('status');
            $table->string('customization_number', 20)->nullable()->after('customization_name');
            $table->text('customization_notes')->nullable()->after('customization_number');
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('customization_jobs', function (Blueprint $table): void {
            $table->dropIndex(['customer_id', 'status']);
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['customer_id', 'customization_name', 'customization_number', 'customization_notes']);
        });
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn(['customization_name', 'customization_number', 'customization_notes']);
        });
    }
};
