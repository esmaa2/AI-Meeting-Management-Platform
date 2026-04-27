<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('display_name')->nullable();
            $table->text('biography')->nullable();
            $table->string('role')->default('member'); // member | admin
            $table->string('plan')->default('free');   // free | pro | enterprise
            $table->string('department')->nullable();
            $table->string('job_title')->nullable();
            $table->string('avatar_url')->nullable();
            $table->bigInteger('storage_used')->default(0); // bytes
            $table->bigInteger('storage_limit')->default(524288000); // 500MB
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
 