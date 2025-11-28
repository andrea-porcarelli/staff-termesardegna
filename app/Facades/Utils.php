<?php

namespace App\Facades;

use Illuminate\Support\Facades\Log;

class Utils
{
    public static function queryLog($models, $print = false, $return = false)
    {
        Log::info($models->toSql());
        if (preg_match('/\?/', $models->toSql())) {
            $query = str_replace(array('?'), array('\'%s\''), $models->toSql());
            $query = vsprintf($query, $models->getBindings());
        } else {
            $query = $models->toSql();
        }
        if ($return) {
            return $query;
        }
        if (!$print) {
            Log::info($query);
        } else {
            echo $query;
        }
    }
}
