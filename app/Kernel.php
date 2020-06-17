<?php


namespace App;


class Kernel
{
    static $env;

    public function run(){
        echo 'WP Converter is ready.'."\n";

        Kernel::$env = (new EnvParser("../.env"))->parse();

        $src  = new DB(
                Kernel::env('SRC_DB_HOST'),
                Kernel::env('SRC_DB_NAME'),
                Kernel::env('SRC_DB_PORT'),
                Kernel::env('SRC_DB_USER'),
                Kernel::env('SRC_DB_PASS'),
            );
        $dest  = new DB(
                Kernel::env('DST_DB_HOST'),
                Kernel::env('DST_DB_NAME'),
                Kernel::env('DST_DB_PORT'),
                Kernel::env('DST_DB_USER'),
                Kernel::env('DST_DB_PASS'),
            );
    }

    static function env($key, $default = null){
        return Kernel::$env[$key] ?? $default;
    }
}