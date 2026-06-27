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
        Schema::create('match_infos', function (Blueprint $table) {

            $table->id();
            $table->foreignId('home_team_id')->nullable()->constrained('teams')->onDelete('set null');
            $table->foreignId('away_team_id')->nullable()->constrained('teams')->onDelete('set null');
            $table->dateTime('match_date')->nullable();
            $table->string('location')->nullable();
            $table->foreignId('tournament_id')->constrained('tournaments')->onDelete('cascade');
            $table->foreignId('round_id')->constrained('rounds')->onDelete('cascade');
            $table->unsignedTinyInteger('home_team_score')->default(0);
            $table->unsignedTinyInteger('away_team_score')->default(0);
            $table->enum('status', ['scheduled', 'completed'])->default('scheduled');
            $table->enum('winner', ['home', 'away', 'draw'])->nullable();
            $table->unsignedTinyInteger('sort_order')->nullable();
            $table->timestamps();

        });
    }
   
    /**
     * 
     * Reverse the migrations.
     * 
     */

    public function down(): void
    {
        Schema::dropIfExists('match_infos');
    }

};
