<?php


namespace App;


class Kernel
{
    static $env;
    private $src,$dest;

    public function run(){
        echo 'WP Converter is ready.'."\n";

        Kernel::$env = (new EnvParser(".env"))->parse();

        $this->src  = new DB(
            Kernel::env('SRC_DB_HOST'),
            Kernel::env('SRC_DB_PORT'),
            Kernel::env('SRC_DB_USER'),
            Kernel::env('SRC_DB_PASS'),
            Kernel::env('SRC_DB_NAME')
            );
        $this->dest  = new DB(
            Kernel::env('DST_DB_HOST'),
            Kernel::env('DST_DB_PORT'),
            Kernel::env('DST_DB_USER'),
            Kernel::env('DST_DB_PASS'),
            Kernel::env('DST_DB_NAME')
            );

        $this->src->connect();
        if($this->src->error){
            die("Source Database Connection Failed!");
        }
        $this->dest->connect();
        if($this->dest->error){
            die("Destination Database Connection Failed!");
        }

        $this->convert_products();
        $this->convert_articles();
    }

    static function env($key, $default = null){
        return Kernel::$env[$key] ?? $default;
    }

    static function dd()
    {
        foreach (func_get_args() as $arg) var_dump($arg);
        die();
    }

    function convert_products(){
        $items = $this->get_products();
        foreach($items as $item){
            echo $item."\n";
        }
    }

    function convert_articles(){
        $items = $this->get_articles();
        foreach($items as $item){
            echo $item."\n";
        }
    }

    function get_products(){
        $items = [1,2,3];
        foreach($items as $item){
            yield $item;
        }
    }

    function get_articles(){
        $items = [4,5,6];
        foreach($items as $item){
            yield $item;
        }
    }
}