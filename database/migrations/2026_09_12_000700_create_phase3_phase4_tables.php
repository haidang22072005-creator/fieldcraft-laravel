<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table): void {
            $table->timestamp('last_activity_at')->nullable()->after('session_key')->index();
            $table->timestamp('contacted_at')->nullable()->after('last_activity_at');
            $table->foreignId('contacted_by')->nullable()->after('contacted_at')->constrained('users')->nullOnDelete();
            $table->foreignId('contact_coupon_id')->nullable()->after('contacted_by')->constrained('coupons')->nullOnDelete();
        });

        Schema::create('cross_sell_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('source_type', 20);
            $table->string('source_value', 150);
            $table->foreignId('recommended_product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedSmallInteger('priority')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['source_type', 'source_value']);
            $table->unique(['source_type', 'source_value', 'recommended_product_id']);
        });

        Schema::create('football_trends', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('source', 30)->default('manual');
            $table->string('provider', 80)->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->json('trend_data')->nullable();
            $table->text('campaign_suggestion')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('matchday_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->foreignId('team_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('banner_path')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('matchday_campaign_product', function (Blueprint $table): void {
            $table->foreignId('matchday_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['matchday_campaign_id', 'product_id']);
        });

        Schema::create('second_hand_listings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('brand', 100);
            $table->string('product_name', 150);
            $table->string('size', 30);
            $table->string('condition', 80);
            $table->json('images')->nullable();
            $table->unsignedInteger('asking_price');
            $table->string('payout_method', 20);
            $table->string('status', 20)->default('submitted')->index();
            $table->unsignedInteger('commission_amount')->default(0);
            $table->unsignedInteger('voucher_bonus')->default(0);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('moderation_note')->nullable();
            $table->timestamp('listed_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();
        });

        Schema::create('boot_passports', function (Blueprint $table): void {
            $table->id();
            $table->string('passport_code', 40)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->json('variant_snapshot');
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->string('stud_type')->nullable();
            $table->date('purchase_date');
            $table->date('warranty_until')->nullable();
            $table->string('review_status', 20)->default('not_reviewed');
            $table->boolean('second_hand_eligible')->default(true);
            $table->timestamps();
            $table->index(['user_id', 'purchase_date']);
        });

        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100)->index();
            $table->nullableMorphs('subject');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('type', 80)->index();
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('boot_passports');
        Schema::dropIfExists('second_hand_listings');
        Schema::dropIfExists('matchday_campaign_product');
        Schema::dropIfExists('matchday_campaigns');
        Schema::dropIfExists('football_trends');
        Schema::dropIfExists('cross_sell_rules');
        Schema::table('carts', function (Blueprint $table): void {
            $table->dropForeign(['contacted_by']);
            $table->dropForeign(['contact_coupon_id']);
            $table->dropColumn(['last_activity_at', 'contacted_at', 'contacted_by', 'contact_coupon_id']);
        });
    }
};
