<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserAttributesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        if (!Schema::hasTable('user_attributes')) {
            Schema::create('user_attributes', function (Blueprint $table) {
                $table->increments('id');
                $table->string('mobile', 16);
                $table->string('attr_name', 64);
                $table->string('attr_value', 256);
                $table->timestamps();
                
                $table->index('mobile');
                $table->index('attr_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('user_attributes');
    }

}
