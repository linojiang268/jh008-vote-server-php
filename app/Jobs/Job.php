<?php
namespace Jihe\Jobs;

use Illuminate\Bus\Queueable;

abstract class Job
{
    /*
    |--------------------------------------------------------------------------
    | Queueable Jobs
    |--------------------------------------------------------------------------
    |
    | This job base class provides a central location to place any logic that
    | is shared across all of your jobs. The trait included with the class
    | provides access to the "queueOn" and "delay" queue helper methods.
    |
    */

    use Queueable;

    /**
     * is triggered when invoking inaccessible methods in an object context.
     *
     * @param $name      string
     * @param $arguments array
     *
     * @return mixed
     * @link http://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.methods
     */
    function __get($name)
    {
//        $name = substr($this->$name, 3);

        return $this->$name;
    }


}
