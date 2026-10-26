<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * الإشعارات:
     *   push_subscriptions  أجهزة المستخدم المفعّل عليها الإشعار (موبايل · كمبيوتر)
     *   app_notifications   صندوق الإشعارات (الجرس) لكل مستخدم
     *   notify_rules        من يستلم كل حدث (يحددهم مدير المخزن)
     */
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('endpoint');
            $table->char('endpoint_hash', 64)->unique();
            $table->string('p256dh', 120);
            $table->string('auth', 40);
            $table->string('device', 120)->nullable();
            $table->timestamp('last_ok_at')->nullable();
            $table->unsignedSmallInteger('fails')->default(0);
            $table->timestamps();
        });

        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('event', 20);
            $table->string('title', 160);
            $table->string('body', 600)->nullable();
            $table->string('url', 500)->nullable();
            $table->string('tag', 80)->nullable();
            $table->boolean('urgent')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::create('notify_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('event', 20);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'event', 'user_id'], 'notify_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notify_rules');
        Schema::dropIfExists('app_notifications');
        Schema::dropIfExists('push_subscriptions');
    }
};