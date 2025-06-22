<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('host');
            $table->integer('port')->default(22);
            $table->string('username');
            $table->text('password')->nullable();
            $table->text('private_key')->nullable();
            $table->string('private_key_password')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_connected_at')->nullable();
            $table->string('status')->default('disconnected'); // disconnected, connected, error
            $table->text('last_error')->nullable();
            $table->timestamps();
            
            $table->unique(['host', 'port', 'username']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('servers');
    }
};