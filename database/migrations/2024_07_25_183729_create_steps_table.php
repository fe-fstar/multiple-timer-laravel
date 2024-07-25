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
        Schema::create('steps', function (Blueprint $table) {
            $table->integer("id");
            $table->foreignUuid("plan_id")->references("id")->on("plans");
            $table->unique(['id', 'plan_id']);
            $table->integer('hours');
            $table->integer('minutes');
            $table->integer('seconds');
            $table->string("title");
            $table->text("description");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('steps');
    }
};
