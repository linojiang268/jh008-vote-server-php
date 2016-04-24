<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityAlbumImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activity_album_images', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('activity_id');
            $table->tinyInteger('creator_type');
            $table->integer('creator_id');
            $table->string('image_url');
            $table->tinyInteger('status');
            
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
        Schema::drop('activity_album_images');
    }
}
