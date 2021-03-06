<?php namespace Lrs\Tracker\Locker\Data;

use Illuminate\Support\Facades\DB;

class BaseData
{

    // protected $db;

    protected function setDB()
    {
        $this->db = DB::getMongoDB();
    }

    /**
     * getMatch is used to match mongo aggregation searches to a specific LRS.
     *
     * @param  lrs     int    The LRS id
     * @return array
     *
     **/
    protected function getMatch($lrs)
    {
        return array('lrs_id' => new \MongoDB\BSON\ObjectID($lrs));
    }

}