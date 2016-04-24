<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityApplicantsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        if (!Schema::hasTable('activity_applicants')) {
            Schema::create('activity_applicants', function (Blueprint $table) {
                $table->increments('id');
                $table->string('order_no', 64);
                $table->integer('activity_id');
                $table->integer('user_id');
                $table->string('mobile', 16);
                $table->string('name', 32);
                $table->text('attrs');
                $table->string('remark', 128)->default('');
                $table->dateTime('expire_at')->nullable();
                $table->tinyInteger('channel')->default(0);
                $table->tinyInteger('status');

                $table->timestamps();
//                $table->softDeletes();
                
                $table->unique('order_no');
                $table->index('activity_id');
                $table->index('user_id');
                $table->index('expire_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('activity_applicants');
    }

}
