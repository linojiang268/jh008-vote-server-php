<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXinnianVotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('xinnian_votes')) {
            Schema::create('xinnian_votes', function (Blueprint $table) {
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
        Schema::drop('xinnian_votes');
    }
}
