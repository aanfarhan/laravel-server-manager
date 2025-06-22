<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('repository');
            $table->string('branch')->default('main');
            $table->string('deploy_path');
            $table->json('pre_deploy_commands')->nullable();
            $table->json('build_commands')->nullable();
            $table->json('post_deploy_commands')->nullable();
            $table->string('ssh_key_path')->nullable();
            $table->string('status')->default('idle'); // idle, deploying, success, failed
            $table->text('last_deployment_log')->nullable();
            $table->timestamp('last_deployed_at')->nullable();
            $table->string('last_commit_hash')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('deployments');
    }
};