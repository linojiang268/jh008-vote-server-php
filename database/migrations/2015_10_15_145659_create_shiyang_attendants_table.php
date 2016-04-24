<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShiyangAttendantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('shiyang_attendants')) {
            Schema::create('shiyang_attendants', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 32);
                $table->tinyInteger('gender')->default(2);
                $table->integer('age');
                $table->char('mobile', 11);
                $table->string('work_unit', 32);
                $table->string('position', 32);
                $table->string('wechat_id', 32);
                $table->string('cover_url', 128);
                $table->text('images_url')->nullable();
                $table->string('talent', 32)->nullable();
                $table->tinyInteger('guest_apply', 0);
                $table->string('motto', 128)->nullable();
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
        Schema::drop('shiyang_attendants');
    }
}
