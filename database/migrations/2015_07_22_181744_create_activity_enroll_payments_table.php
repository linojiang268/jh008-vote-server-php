<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityEnrollPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('activity_enroll_payments')) {
            Schema::create('activity_enroll_payments', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('activity_id')->unsigned();
                $table->integer('user_id')->unsigned();
                $table->integer('fee')->unsigned();
                $table->tinyInteger('channel');             // 1 -- alipay; 2 -- wxpay
                $table->string('order_no', 32)->index();    // 商户订单号
                $table->string('trade_no', 64)->nullable(); // 支付流水号
                $table->timestamp('payed_at')->nullable();  // 支付成功时间
                $table->tinyInteger('status');              // 0 - 待支付;
                // 2 -- 成功;  3 -- 失败
                $table->timestamps();

                $table->index(['activity_id', 'status', 'created_at']);
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
        Schema::drop('activity_enroll_payments');
    }
}
