<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->unsignedInteger('per_user_limit')->nullable()->after('usage_limit');
        });

        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamp('used_at');
            $table->timestamps();
            $table->index(['coupon_id', 'user_id']);
        });

        DB::table('orders')
            ->whereNotNull('coupon_id')
            ->whereNotNull('user_id')
            ->where('status', '!=', 'cancelled')
            ->orderBy('id')
            ->chunkById(200, function ($orders) {
                foreach ($orders as $order) {
                    DB::table('coupon_usages')->insertOrIgnore([
                        'coupon_id' => $order->coupon_id,
                        'user_id' => $order->user_id,
                        'order_id' => $order->id,
                        'used_at' => $order->created_at ?? now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
        Schema::table('coupons', fn (Blueprint $table) => $table->dropColumn('per_user_limit'));
    }
};
