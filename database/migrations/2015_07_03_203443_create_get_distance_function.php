<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGetDistanceFunction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // calcuate surface distance
        // 6371000 is the mean radius of the Earth in meters
        DB::unprepared(<<<EOF
            CREATE FUNCTION `GET_DISTANCE` (`lat1` DECIMAL,
                                            `lon1` DECIMAL,
                                            `lat2` DECIMAL,
                                            `lon2` DECIMAL)
            RETURNS DECIMAL(10,0)
            NO SQL
            BEGIN
                DECLARE v DOUBLE;
                SELECT COS(RADIANS(`lat1`)) * COS(RADIANS(`lat2`)) * COS(RADIANS(`lon2`) - RADIANS(`lon1`)) +
                       SIN(RADIANS(`lat1`)) * SIN(RADIANS(`lat2`)) INTO v;
                RETURN 6371 * ACOS(v);
            END
EOF
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP FUNCTION IF EXISTS `GET_DISTANCE`');
    }
}
