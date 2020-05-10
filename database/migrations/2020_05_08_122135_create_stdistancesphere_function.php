<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class CreateStdistancesphereFunction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**
         * Creates a function in the database that allows
         * the computation of the geographical distance
         * between two existing points on Earth.
         * 
         * This function is for MariaDB databases since
         * they do not have the implementation for the
         * MySQL "st_distance_sphere" function.
         * 
         * More at: https://dev.mysql.com/doc/refman/5.7/en/spatial-convenience-functions.html#function_st-distance-sphere
         * 
         * @param Point Latitude and longitude of origin
         * @param Point Latitude and longitude of destination
         * 
         * @return float Distance between points
         * 
         */
        DB::unprepared(
            'CREATE FUNCTION st_distance_sphere IF NOT EXISTS (point1 POINT, point2 POINT)
            RETURNS decimal(10,2)
            BEGIN
            return 6371000 * 2 * ASIN(SQRT(
               POWER(SIN((ST_Y(point2) - ST_Y(point1)) * pi()/180 / 2),
               2) + COS(ST_Y(point1) * pi()/180 ) * COS(ST_Y(point2) *
               pi()/180) * POWER(SIN((ST_X(point2) - ST_X(point1)) *
               pi()/180 / 2), 2) ));
            END'
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP IF EXISTS st_distance_sphere');
    }
}
