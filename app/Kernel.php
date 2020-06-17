<?php


namespace App;


class Kernel
{
    static $env;

    public function run(){
        echo 'WP Converter is ready.'."\n";

        Self::$env = (new EnvParser("../.env"))->parse();

        $src  = new DB(
                env('SRC_DB_HOST'),
                env('SRC_DB_NAME'),
                env('SRC_DB_PORT'),
                env('SRC_DB_USER'),
                env('SRC_DB_PASS'),
            );
        $dest  = new DB(
                env('DST_DB_HOST'),
                env('DST_DB_NAME'),
                env('DST_DB_PORT'),
                env('DST_DB_USER'),
                env('DST_DB_PASS'),
            );
    }

    static function env($key, $default = null){
        return Self::$env[$key] ?? $default;
    }
}