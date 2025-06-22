<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('monitoring_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->decimal('cpu_usage', 5, 2)->nullable();
            $table->decimal('memory_usage', 5, 2)->nullable();
            $table->decimal('disk_usage', 5, 2)->nullable();
            $table->decimal('load_1min', 8, 2)->nullable();
            $table->decimal('load_5min', 8, 2)->nullable();
            $table->decimal('load_15min', 8, 2)->nullable();
            $table->bigInteger('uptime_seconds')->nullable();
            $table->integer('process_count')->nullable();
            $table->bigInteger('network_bytes_received')->nullable();
            $table->bigInteger('network_bytes_transmitted')->nullable();
            $table->json('additional_metrics')->nullable();
            $table->timestamps();
            
            $table->index(['server_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('monitoring_logs');
    }
};