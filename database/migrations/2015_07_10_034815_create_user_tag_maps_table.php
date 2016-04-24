<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserTagMapsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_tag_maps', function (Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->integer('tag_id')->unsigned();

            $table->increments('id');
            $table->timestamps();

            $table->index(['user_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_tag_maps');
    }
}
