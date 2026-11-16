<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deposits')) {
            return;
        }

        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30)->default('cashmaal')->index();
            $table->string('order_id', 80)->unique();
            $table->decimal('amount', 12, 2);
            $table->decimal('fee', 12, 2)->default(0);
            $table->decimal('credit_amount', 12, 2)->default(0);
            $table->string('currency', 8)->default('PKR');
            $table->string('status', 20)->default('pending')->index();
            $table->string('cm_tid', 64)->nullable()->index();
            $table->string('client_email')->nullable();
            $table->text('admin_notes')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
