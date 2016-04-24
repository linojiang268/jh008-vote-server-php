<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityEnrollIncomesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('activity_enroll_payments')) {
            Schema::create('activity_enroll_incomes', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('team_id')->unsigned()->index();
                $table->integer('activity_id')->unsigned();
                $table->integer('total_fee')->default(0);
                $table->integer('transfered_fee')->default(0);
                $table->text('financial_action_result')->nullable();
                $table->timestamp('enroll_end_time');
                $table->tinyInteger('status')->default(1)->index();     // 1 -- wait for tranfer
                // 2 -- transferring
                // 3 -- tranfer finish
                $table->timestamps();

                $table->index(['enroll_end_time', 'status']);
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
        Schema::drop('activity_enroll_incomes');
    }
}
