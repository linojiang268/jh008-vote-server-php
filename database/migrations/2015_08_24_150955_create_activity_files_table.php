<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activity_files', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('activity_id');
            $table->string('name', 128);
            $table->string('memo', 255)->nullable();
            $table->bigInteger('size')->nullalbe();
            $table->string('extension', 32)->nullable();
            $table->string('url', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('activity_files');
    }
}
