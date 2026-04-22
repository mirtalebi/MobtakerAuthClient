<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sso_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('sso_id')->unique();
            $table->longText('sso_data')->nullable();
            $table->longText('token')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index('sso_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_users');
    }
};
