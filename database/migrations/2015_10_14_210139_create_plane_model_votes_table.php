<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlaneModelVotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('plane_model_votes')) {
            Schema::create('plane_model_votes', function (Blueprint $table) {
                $table->increments('id');
                $table->string('voter', 32)->index();
                $table->integer('type')->index();
                $table->integer('user_id')->index();
                $table->timestamps();

                $table->index(['voter', 'user_id', 'type']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('plane_model_votes');
    }
}
