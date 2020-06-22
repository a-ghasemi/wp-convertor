<?php


namespace App;


class Kernel
{
    static $env;
    private $src, $dest, $main_db;

    public function run()
    {
        echo 'WP Converter is ready.' . "\n";

        Kernel::$env = (new EnvParser(".env"))->parse();

        $this->main_db = new DB(
            Kernel::env('MAIN_DB_HOST'),
            Kernel::env('MAIN_DB_PORT'),
            Kernel::env('MAIN_DB_USER'),
            Kernel::env('MAIN_DB_PASS'),
            Kernel::env('MAIN_DB_NAME')
        );
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

        $this->main_db->connect();
        if ($this->main_db->error) {
            die("Main Database Connection Failed!");
        }
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
            $post_id = $item['ID'];

            if( in_array($post_id , ['138','148']) )continue;

            $x++;
            if ($x >= 2) break;

            $img_item = $this->get_image($post_id);

            $new_post = [
//                'old_post_id' => $post_id,

                'post_name'    => $this->get_meta($post_id, 'product_latin_name'),
                'post_title'   => str_replace(" ","-",$item['post_title']),
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
                'post_status'           => 'publish',
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
                'post_content'          => '',
                'post_title'            => strtoupper($item['post_title']),
                'post_excerpt'          => '',
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


            $meta_keys = [
                '_manage_stock',
                '_tax_class',
                '_tax_status',
                'total_sales',
                '_edit_last',
                '_edit_lock',
                '_thumbnail_id',
                '_product_version',
                'rank_math_internal_links_processed',
                '_fusion',
                '_wc_review_count',
                '_wc_average_rating',
                '_stock_status',
                '_stock',
                '_download_expiry',
                '_backorders',
                '_sold_individually',
                '_virtual',
                '_downloadable',
                '_download_limit',
                'rank_math_primary_product_cat',
                'rank_math_seo_score',
                'rank_math_robots',
                'rank_math_advanced_robots',
                'rank_math_dont_show_seo_score',
                'rank_math_permalink',
                'rank_math_twitter_card_type',
                'rank_math_twitter_enable_image_overlay',
                'rank_math_twitter_image_overlay',
                'rank_math_twitter_use_facebook',
                'rank_math_facebook_image_overlay',
                'rank_math_facebook_enable_image_overlay',
                'rank_math_rich_snippet',
            ];

            $meta_values = [
                'rank_math_internal_links_processed' => '',
                '_fusion' => '',
                '_wc_review_count' => '0',
                '_wc_average_rating' => '0',
                '_stock_status' => 'instock',
                '_stock' => '',
                '_download_expiry' => '',
                '_backorders' => '',
                '_sold_individually' => '',
                '_virtual' => '',
                '_downloadable' => '',
                '_download_limit' => '',
                'rank_math_primary_product_cat' => '',
                'rank_math_seo_score' => '',
                'rank_math_robots' => '',
                'rank_math_advanced_robots' => '',
                'rank_math_dont_show_seo_score' => '',
                'rank_math_permalink' => '',
                'rank_math_twitter_card_type' => '',
                'rank_math_twitter_enable_image_overlay' => '',
                'rank_math_twitter_image_overlay' => '',
                'rank_math_twitter_use_facebook' => '',
                'rank_math_facebook_image_overlay' => '',
                'rank_math_facebook_enable_image_overlay' => '',
                'rank_math_rich_snippet' => '',
            ];


            $metas = [];
            foreach($meta_keys as $key){
                $metas[] = "(".implode(",",[
                    'post_id' => $post_id,
                    'meta_key' => $key,
                    'meta_value' => $meta_values[$key],
                ]).")";
            }

            $this->dest->begin_transaction();
            $a = $this->insert_into("posts", $new_post);
            $b = $this->insert_into("posts", $common_details_img);
            $c = $this->insert_into_bulk("postmeta", $meta_keys, $metas);
            $c = true;
            if ($a && $b && $c) {
                echo "$post_id converted\n";
                $this->dest->commit();
            } else {
                echo "$post_id convertion failed ###########\n";
                echo "ERROR: {$this->dest->error}\n";
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

    function insert_into($table_name, $rec)
    {
        $keys = implode('`,`', array_keys($rec));
        $values = implode('\',\'', array_values($rec));

        $qry = "insert into " . Kernel::env('DST_DB_PREFIX') . "$table_name " .
            " (`$keys`) " .
            "VALUES ('$values');";

        return $this->dest->insert($qry);
    }

    function insert_into_bulk($table_name, $keys, $values)
    {
        $keys = implode('`,`', $keys);
        $values = implode(',', $values);

        $qry = "insert into " . Kernel::env('DST_DB_PREFIX') . "$table_name " .
            " (`$keys`) " .
            "VALUES ('$values');";

        return $this->dest->insert($qry);
    }

    function ch_domain($inp)
    {
        $out = str_replace('http://', 'https://', $inp);
        $out = str_replace('https://miracontrol.ir/', 'https://miracontroller.ir/', $out);
        $out = str_replace('https://behinnogen.ir/', 'https://miracontroller.ir/', $out);

        return $out;
    }


    function setup(){
        $this->main_db->execute(
            "CREATE TABLE posts ( ".
            "post_id int, " .
            "post_type varchar(100), " .
            "status varchar(100), " .
            "created_at timestamp, " .
            "updated_at timestamp " .
            ")"
        );
    }
}
