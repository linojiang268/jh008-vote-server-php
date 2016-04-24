<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('creator_id');
            $table->integer('city_id');
            $table->string('name', 32);
            $table->string('email', 64)->nullalbe();
            $table->string('logo_url', 255);
            $table->string('address', 128)->nullable();
            $table->string('contact_phone', 32)->nullable();    // phone number of contacts
            $table->string('contact', 32)->nullable();  // name of contacts
            $table->string('introduction', 255)->nullable();
            $table->tinyInteger('certification')->default(0);
            $table->string('qr_code_url', 255)->nullable();
            $table->tinyInteger('join_type')->default(0);
            $table->tinyInteger('status')->default(0);
            $table->timestamp('activities_updated_at')->nullable();
            $table->timestamp('members_updated_at')->nullable();
            $table->timestamp('news_updated_at')->nullable();
            $table->timestamp('albums_updated_at')->nullable();
            $table->timestamp('notices_updated_at')->nullable();
            $table->text('tags')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('teams');
    }
}
