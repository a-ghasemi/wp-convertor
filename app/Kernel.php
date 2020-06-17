<?php


namespace App;


class Kernel
{
    static $env;

    public function run(){
        echo 'WP Converter is ready.'."\n";

        Kernel::$env = (new EnvParser(".env"))->parse();

        $src  = new DB(
            Kernel::env('SRC_DB_HOST'),
            Kernel::env('SRC_DB_PORT'),
            Kernel::env('SRC_DB_USER'),
            Kernel::env('SRC_DB_PASS'),
            Kernel::env('SRC_DB_NAME')
            );
        $dest  = new DB(
            Kernel::env('DST_DB_HOST'),
            Kernel::env('DST_DB_PORT'),
            Kernel::env('DST_DB_USER'),
            Kernel::env('DST_DB_PASS'),
            Kernel::env('DST_DB_NAME')
            );

        $src->connect();
        $dest->connect();

        Kernel::dd($src->error, $dest->error);

    }

    static function env($key, $default = null){
        return Kernel::$env[$key] ?? $default;
    }

    static function dd()
    {
        foreach (func_get_args() as $arg) var_dump($arg);
        die();
    }

}