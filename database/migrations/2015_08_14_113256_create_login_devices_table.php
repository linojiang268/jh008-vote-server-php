<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLoginDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('login_devices', function (Blueprint $table) {
            $table->increments('id');
            $table->char('mobile', 11);
            $table->tinyInteger('source');
            $table->string('identifier', 60);
            $table->string('old_identifier', 60)->default('');  // old identifier, generate by user login before
            $table->timestamps();

            $table->unique(['mobile', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('login_devices');
    }
}
