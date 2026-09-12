<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->index('ghn_order_code'));
        Schema::table('products', function (Blueprint $table): void { $table->index('name'); $table->index('brand'); });
        Schema::table('users', fn (Blueprint $table) => $table->index('phone'));
        Schema::table('second_hand_listings', function (Blueprint $table): void { $table->index('product_name'); $table->index('brand'); });
    }

    public function down(): void
    {
        Schema::table('second_hand_listings', function (Blueprint $table): void { $table->dropIndex(['product_name']); $table->dropIndex(['brand']); });
        Schema::table('users', fn (Blueprint $table) => $table->dropIndex(['phone']));
        Schema::table('products', function (Blueprint $table): void { $table->dropIndex(['name']); $table->dropIndex(['brand']); });
        Schema::table('orders', fn (Blueprint $table) => $table->dropIndex(['ghn_order_code']));
    }
};
