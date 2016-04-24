<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlaneModelAttendantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('plane_model_attendants')) {
            Schema::create('plane_model_attendants', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 32);
                $table->tinyInteger('gender')->default(2);
                $table->integer('height');
                $table->integer('weight');
                $table->string('bwh', 32);
                $table->float('shoe_size');
                $table->char('mobile', 11);
                $table->string('cover_url', 128);
                $table->text('images_url')->nullable();
                $table->string('intro', 128);
                $table->tinyInteger('status');

                $table->timestamps();
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
        Schema::drop('plane_model_attendants');
    }
}
