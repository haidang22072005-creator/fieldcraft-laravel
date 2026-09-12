<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loyalty_point_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->integer('points');
            $table->string('event_key', 100)->unique();
            $table->string('reason', 255)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'type']);
        });

        DB::table('orders')->where('status', 'completed')->whereNotNull('user_id')->orderBy('id')->chunkById(200, function ($orders): void {
            foreach ($orders as $order) {
                $points = intdiv((int) $order->total, 10000);
                if ($points > 0) DB::table('loyalty_point_transactions')->insertOrIgnore(['user_id' => $order->user_id, 'order_id' => $order->id, 'type' => 'earn', 'points' => $points, 'event_key' => 'order:'.$order->id.':completed', 'reason' => 'Đơn hàng hoàn thành', 'created_at' => $order->completed_at ?? $order->updated_at ?? now(), 'updated_at' => now()]);
            }
        });
    }

    public function down(): void { Schema::dropIfExists('loyalty_point_transactions'); }
};
