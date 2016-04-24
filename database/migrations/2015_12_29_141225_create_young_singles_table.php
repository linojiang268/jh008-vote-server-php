<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateYoungSinglesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('young_singles')) {
            Schema::create('young_singles', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('order_id')->default(0);
                $table->string('name', 32);
                $table->char('id_number', 18);
                $table->tinyInteger('gender')->default(2);
                $table->string('date_of_birth', 16);
                $table->integer('height');
                $table->string('graduate_university', 128);
                $table->integer('degree');
                $table->integer('yearly_salary');
                $table->string('work_unit', 128);
                $table->char('mobile', 11);
                $table->string('cover_url', 128);
                $table->text('images_url')->nullable();
                $table->string('talent', 128)->nullable();
                $table->string('mate_choice', 128)->nullable();
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
        Schema::drop('young_singles');
    }
}
