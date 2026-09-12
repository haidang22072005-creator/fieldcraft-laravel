<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->string('stud_type', 10)->nullable()->after('size');
            $table->string('foot_shape', 20)->nullable()->after('stud_type');
            $table->string('surface_type', 50)->nullable()->after('foot_shape');
            $table->unsignedInteger('low_stock_threshold')->nullable()->after('surface_type');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', fn (Blueprint $table) => $table->dropColumn(['stud_type', 'foot_shape', 'surface_type', 'low_stock_threshold']));
    }
};
