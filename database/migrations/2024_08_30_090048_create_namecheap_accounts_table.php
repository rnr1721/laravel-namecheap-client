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
        Schema::create('namecheap_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('username')->required();
            $table->string('email')->nullable();
            $table->string('api_key')->required();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('namecheap_accounts');
    }
};
