<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('recipient_name', 100)->nullable()->after('address_id');
            $table->string('recipient_phone', 25)->nullable()->after('recipient_name');
            $table->string('recipient_email')->nullable()->after('recipient_phone');
            $table->string('province', 100)->nullable()->after('recipient_email');
            $table->string('district', 100)->nullable()->after('province');
            $table->string('ward', 100)->nullable()->after('district');
            $table->string('address_line')->nullable()->after('ward');
            $table->text('note')->nullable()->after('address_line');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'recipient_name', 'recipient_phone', 'recipient_email', 'province',
                'district', 'ward', 'address_line', 'note',
            ]);
        });
    }
};
