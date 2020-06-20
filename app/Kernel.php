<?php


namespace App;


class Kernel
{
    static $env;
    private $src, $dest;

    public function run()
    {
        echo 'WP Converter is ready.' . "\n";

        Kernel::$env = (new EnvParser(".env"))->parse();

        $this->src = new DB(
            Kernel::env('SRC_DB_HOST'),
            Kernel::env('SRC_DB_PORT'),
            Kernel::env('SRC_DB_USER'),
            Kernel::env('SRC_DB_PASS'),
            Kernel::env('SRC_DB_NAME')
        );
        $this->dest = new DB(
            Kernel::env('DST_DB_HOST'),
            Kernel::env('DST_DB_PORT'),
            Kernel::env('DST_DB_USER'),
            Kernel::env('DST_DB_PASS'),
            Kernel::env('DST_DB_NAME')
        );

        $this->src->connect();
        if ($this->src->error) {
            die("Source Database Connection Failed!");
        }
        $this->dest->connect();
        if ($this->dest->error) {
            die("Destination Database Connection Failed!");
        }

        $this->convert_products();
        $this->convert_articles();
    }

    static function env($key, $default = null)
    {
        return Kernel::$env[$key] ?? $default;
    }

    static function dd()
    {
        foreach (func_get_args() as $arg) var_dump($arg);
        die();
    }

    function convert_products()
    {
        $items = $this->get_products();

        $x = 0;
        foreach ($items as $item) {
            $x++;
            if ($x >= 2) break;

            $post_id = $item['ID'];
            $img_item = $this->get_image($post_id);


            $type = 'product';
            $new_post = [
                'old_post_id' => $post_id,

                'post_name'    => $this->get_meta($post_id, 'product_latin_name'),
                'post_title'   => $item['post_title'],
                'post_excerpt' => $this->get_meta($post_id, '_yoast_wpseo_metadesc'),//summary
                'post_content' => $item['post_content'] . "<hr/>" . $this->get_meta($post_id, 'product_key_features'),
//                'product_technical_informations' => $this->get_meta($post_id,'product_technical_informations'), //useless
            ];
            $common_details = [
                //                'ID', // autoincremental
                'post_author'           => 1,
                'post_date'             => $item['post_date'],
                'post_date_gmt'         => $item['post_date_gmt'],
                //                'post_content' => '',
                //                'post_title' => '',
                //                'post_excerpt' => '',
                'post_status'           => 'publish' ,
                'comment_status'        => 'open',
                'ping_status'           => 'closed',
                'post_password'         => '',
                //                'post_name' => '',
                'to_ping'               => '',
                'pinged'                => '',
                'post_modified'         => $item['post_modified'],
                'post_modified_gmt'     => $item['post_modified_gmt'],
                'post_content_filtered' => '',
                'post_parent'           => 0,
                'guid'                  => $this->ch_domain($item['guid']),
                'menu_order'            => 0,
                'post_type'             => 'product',
                'post_mime_type'        => '',
                'comment_count'         => 0,
            ];
            $new_post = array_merge($new_post, $common_details);


//                '_wp_attachment_image_alt' => $this->get_meta($post_id,'_wp_attachment_image_alt'),

            $common_details_img = [
                //                'ID', // autoincremental
                'post_author'           => 1,
                'post_date'             => $img_item['post_date'],
                'post_date_gmt'         => $img_item['post_date_gmt'],
                'post_content' => '',
                'post_title' => strtoupper($item['post_title']),
                'post_excerpt' => '',
                'post_status'           => 'inherit',
                'comment_status'        => 'open',
                'ping_status'           => 'closed',
                'post_password'         => '',
                'post_name'             => $item['post_title'],
                'to_ping'               => '',
                'pinged'                => '',
                'post_modified'         => $img_item['post_modified'],
                'post_modified_gmt'     => $img_item['post_modified_gmt'],
                'post_content_filtered' => '',
                'post_parent'           => $post_id,
                'guid'                  => $this->ch_domain($img_item['guid']),
                'menu_order'            => 0,
                'post_type'             => 'attachment',
                'post_mime_type'        => $img_item['post_mime_type'],
                'comment_count'         => 0,
            ];

            $this->dest->begin_transaction();
            $a = $this->insert_post($new_post);
            $b = $this->insert_post($common_details_img);
            if($a && $b){
                echo "$post_id converted\n";
                $this->dest->commit();
            }
            else{
                echo "$post_id convertion failed ###########\n";
                $this->dest->rollback();
            }
        }
    }

    function convert_articles()
    {
        $items = $this->get_articles();
        foreach ($items as $item) {
            echo $item . "\n";
        }
    }

    function get_products()
    {
        $items = $this->src->select(
            "select * from " . Kernel::env('SRC_DB_PREFIX') . "posts " .
            "where post_status = 'publish' " .
            "and post_type = 'product' ");
        foreach ($items as $item) {
            yield $item;
        }
    }

    function get_meta($post_id, $meta_key)
    {
        $meta = $this->src->select_one(
            "select meta_value from " . Kernel::env('SRC_DB_PREFIX') . "postmeta " .
            "where post_id = '$post_id' " .
            "and meta_key = '$meta_key' ");

        return $meta['meta_value'] ?? null;
    }

    function get_image($post_id)
    {
        $img = $this->src->select_one(
            "select * from " . Kernel::env('SRC_DB_PREFIX') . "posts " .
            "where post_parent = '$post_id' " .
            "and post_type = 'attachment' " .
            "ORDER BY `post_date` ");

        return $img ?? null;

    }

    function get_articles()
    {
        $items = $this->src->select(
            "select * from " . Kernel::env('SRC_DB_PREFIX') . "posts " .
            "where post_status = 'publish' " .
            "and post_type = 'article' ");
        foreach ($items as $item) {
            yield $item;
        }
    }

    function insert_post($rec){
        $keys = implode('`,`',array_keys($rec));
        $values = implode('\',\'',array_values($rec));

        return $this->dest->insert(
            "insert into " . Kernel::env('SRC_DB_PREFIX') . "posts " .
            " (`$keys`) " .
            " ('$values') "
        );
    }

    function ch_domain($inp){
        $out = str_replace('http://','https://',$inp);
        $out = str_replace('https://miracontrol.ir/','https://miracontroller.ir/',$out);

        return $out;
    }
}