<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider'); // zoom | slack | asana | microsoft_teams
            $table->string('status')->default('disconnected'); // connected | disconnected | error
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->json('meta')->nullable(); // e.g. {"channel": "#meetings"}
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};