<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method', 20)->default('cod')->change();
            $table->string('payment_status', 20)->default('unpaid')->change();
            $table->string('status', 30)->default('pending')->change();
        });

        DB::table('orders')->where('payment_method', 'online')->update(['payment_method' => 'momo']);
        DB::table('orders')->where('payment_method', 'cod')->where('payment_status', 'pending')->update(['payment_status' => 'unpaid']);

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30)->index();
            $table->string('request_id', 50)->unique();
            $table->string('provider_order_id', 50)->nullable();
            $table->string('transaction_id', 100)->nullable();
            $table->unsignedBigInteger('amount');
            $table->string('status', 20)->index();
            $table->integer('result_code')->nullable();
            $table->string('message')->nullable();
            $table->text('pay_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        DB::table('orders')->where('payment_method', 'momo')->update(['payment_method' => 'online']);
        DB::table('orders')->where('payment_status', 'unpaid')->update(['payment_status' => 'pending']);
        DB::table('orders')->where('status', 'pending_payment')->update(['status' => 'pending']);
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_method', ['cod', 'online'])->change();
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending')->change();
            $table->enum('status', ['pending', 'preparing', 'shipping', 'completed', 'cancelled'])->default('pending')->change();
        });
    }
};
