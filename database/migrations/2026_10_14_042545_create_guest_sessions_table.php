<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up(): void
{
    Schema::create('guest_sessions', function (Blueprint $table) {
        $table->id();
        $table->uuid('guest_id')->unique();
        $table->string('token_hash', 64)->unique();
        $table->timestamp('expires_at')->nullable();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('guest_sessions');
}
};
