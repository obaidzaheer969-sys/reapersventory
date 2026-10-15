<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class EnsuresNotificationsTable
{
    public static function run(): bool
    {
        try {
            if (Schema::hasTable('notifications')) {
                return true;
            }

            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->string('notifiable_type');
                $table->unsignedBigInteger('notifiable_id');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['notifiable_type', 'notifiable_id']);
            });

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
