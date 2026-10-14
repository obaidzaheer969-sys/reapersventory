<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_method_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('PKR');
            $table->string('billing_interval', 16);
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->string('status', 32)->default('pending')->index();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['subscription_id', 'status']);
            // Prevent duplicate approved billing periods for the same subscription.
            $table->unique(
                ['subscription_id', 'period_start', 'period_end', 'status'],
                'subscription_payments_period_status_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
