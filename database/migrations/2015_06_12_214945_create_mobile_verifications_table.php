<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMobileVerificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('mobile_verifications')) {
            Schema::create('mobile_verifications', function (Blueprint $table) {
                $table->increments('id');
                $table->char('mobile', 11);
                $table->string('code', 6);
                $table->timestamp('expired_at');
                $table->timestamps();
                $table->softDeletes();
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
        Schema::drop('mobile_verifications');
    }
}
