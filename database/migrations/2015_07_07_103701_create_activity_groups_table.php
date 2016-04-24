<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('activity_groups')) {
            Schema::create('activity_groups', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('activity_id');
                $table->string('name', 32)->nullable();
                $table->timestamps();
                
                $table->index('activity_id');
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
        Schema::drop('activity_groups');
    }
}
