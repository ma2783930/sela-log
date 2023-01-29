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
        Schema::create('sela_detail_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('actionlog_id')->references('id')->on('sela_action_logs')->cascadeOnUpdate()->cascadeOnUpdate();
            $table->string('data_tag', 30);
            $table->text('value');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('sela_detail_logs');
    }
};
