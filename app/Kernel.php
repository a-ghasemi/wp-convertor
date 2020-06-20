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

        $x = 0;
        foreach($items as $item){
            $x++;
            if($x >= 2) break;
            $post_id = $item['ID'];
            $rec = [
                'old_post_id' => $post_id,
                'product_key_features' => $this->get_meta($post_id,'product_key_features'),
                '_yoast_wpseo_metadesc' => $this->get_meta($post_id,'_yoast_wpseo_metadesc'),
            ];
            print_r($rec)."\n";
        }
    }

    function convert_articles(){
        $items = $this->get_articles();
        foreach($items as $item){
            echo $item."\n";
        }
    }

    function get_products(){
        $items = $this->src->select(
            "select * from " . Kernel::env('SRC_DB_PREFIX') . "posts " .
            "where post_status = 'publish' " .
            "and post_type = 'product' " );
        foreach($items as $item){
            yield $item;
        }
    }

    function get_meta($post_id,$meta_key){
        $meta = $this->src->select_one(
            "select meta_value from " . Kernel::env('SRC_DB_PREFIX') . "postmeta " .
            "where post_id = '$post_id' " .
            "and meta_key = '$meta_key' " );

        return $meta['meta_value'] ?? null;

    }

    function get_articles(){
        $items = $this->src->select(
            "select * from " . Kernel::env('SRC_DB_PREFIX') . "posts " .
            "where post_status = 'publish' " .
            "and post_type = 'article' " );
        foreach($items as $item){
            yield $item;
        }
    }
}