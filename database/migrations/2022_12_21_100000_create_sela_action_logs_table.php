<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('sela_action_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('process_tag', 50);
            $table->string('parent_proc')->nullable();
            $table->string('user_name', 50);
            $table->string('timestamp', 32);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('sela_action_logs');
    }
};
