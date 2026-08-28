<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 25)->nullable(); $table->string('gender', 20)->nullable(); $table->date('birthday')->nullable();
            $table->string('avatar')->nullable(); $table->string('locale', 5)->default('vi'); $table->string('theme', 12)->default('system');
            $table->boolean('marketing_opt_in')->default(true); $table->boolean('order_updates_opt_in')->default(true);
            $table->unsignedInteger('loyalty_points')->default(0); $table->string('loyalty_tier')->default('Đồng');
        });
    }
    public function down(): void { Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['phone','gender','birthday','avatar','locale','theme','marketing_opt_in','order_updates_opt_in','loyalty_points','loyalty_tier'])); }
};
