<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('team_id')->nullable();
            $table->integer('initiator_id');
            $table->integer('city_id');
            $table->string('name', 32);
            $table->string('email', 64)->nullalbe();
            $table->string('logo_url', 255);
            $table->string('address', 128)->nullable();
            $table->string('contact_phone', 32)->nullable();    // phone number of contacts
            $table->string('contact', 32)->nullable();  // name of contacts
            $table->string('introduction', 255)->nullable();
            $table->tinyInteger('status')->default(\Jihe\Entities\TeamRequest::STATUS_PENDING);
            $table->tinyInteger('read')->default(0);
            $table->string('memo', 128)->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('team_requests');
    }
}
