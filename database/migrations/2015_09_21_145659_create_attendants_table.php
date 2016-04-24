<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAttendantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('attendants')) {
            Schema::create('attendants', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 32);
                $table->tinyInteger('gender')->default(2);
                $table->integer('age');
                $table->integer('height');
                $table->string('speciality', 32);
                $table->string('school', 32);
                $table->string('major', 32);
                $table->string('education', 32);
                $table->string('graduation_time', 32);
                $table->char('ident_code', 18);
                $table->char('mobile', 11);
                $table->string('wechat_id', 32);
                $table->string('email', 128);
                $table->string('cover_url', 128);
                $table->text('images_url')->nullable();
                $table->string('motto', 128);
                $table->text('introduction')->nullable();
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
        Schema::drop('attendants');
    }
}
