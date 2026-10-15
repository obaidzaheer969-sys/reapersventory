<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnsureNotificationsTableCommand extends Command
{
    protected $signature = 'reapersventory:ensure-notifications';

    protected $description = 'Create the notifications table if it is missing (fixes empty Alerts on hosting)';

    public function handle(): int
    {
        if (Schema::hasTable('notifications')) {
            $this->info('notifications table already exists.');

            return self::SUCCESS;
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

        $this->info('notifications table created.');

        return self::SUCCESS;
    }
}
