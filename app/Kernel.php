<?php


namespace App;


class Kernel
{
    static $env;
    private $src, $dest, $main_db;

    public function __construct()
    {
        Kernel::$env = (new EnvParser(".env"))->parse();

        $this->main_db = new DB(
            Kernel::env('MAIN_DB_HOST'),
            Kernel::env('MAIN_DB_PORT'),
            Kernel::env('MAIN_DB_USER'),
            Kernel::env('MAIN_DB_PASS'),
            Kernel::env('MAIN_DB_NAME'),
            false
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
    }

    public function run()
    {
        $converted_products = $this->main_get_converted_products();

        $products = $this->src_get_products($converted_products);
        foreach ($products as $product) {
            $post_id = $product['ID'];
            echo "\nconverting record [post_id:$post_id] ";
//            echo "Press [Enter] to continue on next record [post_id:$post_id]";
//            readline();

            $image = $this->src_get_image($post_id);

            $a = true;
            $message = '';
            $this->dest->begin_transaction();
            $dest_id = $this->dest_insert_product($product);
            $a |= $dest_id;
            if($image) $a |= $this->dest_insert_image($image);
            else{
                $message = "has no image !";
                echo " | " . $message;
            }
//            $a |= $this->dest_insert_meta($product);

            if ($a) {
                $ret = $this->main_insert_product("done", intval($post_id), $message, intval($dest_id));
                $this->dest->commit();
            } else {
                $ret = $this->main_insert_product("failed", intval($post_id), $message);
                $this->dest->rollback();
            }
        }

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

    function dest_insert_product($item)
    {
        $post_id = $item['ID'];

        $new_post = [
            'post_name'    => $this->src_get_meta($post_id, 'product_latin_name'),
            'post_title'   => str_replace(" ", "-", $item['post_title']),
            'post_excerpt' => $this->src_get_meta($post_id, '_yoast_wpseo_metadesc'),//summary
            'post_content' => $item['post_content'] . "<hr/>" . $this->src_get_meta($post_id, 'product_key_features'),
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

        $dest_id = null;
        if (!$dest_id = $this->dest_insert_into("posts", $new_post)) {
            echo "$post_id convertion failed ###########\n";
            echo "ERROR: {$this->dest->error}\n";
            return false;
        }
        return $dest_id;
    }

    function dest_insert_image($item)
    {
        $post_id = $item['ID'];

        $common_details_img = [
            //                'ID', // autoincremental
            'post_author'           => 1,
            'post_date'             => $item['post_date'],
            'post_date_gmt'         => $item['post_date_gmt'],
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
            'post_modified'         => $item['post_modified'],
            'post_modified_gmt'     => $item['post_modified_gmt'],
            'post_content_filtered' => '',
            'post_parent'           => $post_id,
            'guid'                  => $this->ch_domain($item['guid']),
            'menu_order'            => 0,
            'post_type'             => 'attachment',
            'post_mime_type'        => $item['post_mime_type'] ?? 'image/jpeg',
            'comment_count'         => 0,
        ];

        if (!$this->dest_insert_into("posts", $common_details_img)) {
            echo "$post_id convertion failed ###########\n";
            echo "ERROR: {$this->dest->error}\n";
            return false;
        }
        return true;
    }

    function dest_insert_meta($item)
    {
        $post_id = $item['ID'];

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
            'rank_math_internal_links_processed'      => '',
            '_fusion'                                 => '',
            '_wc_review_count'                        => '0',
            '_wc_average_rating'                      => '0',
            '_stock_status'                           => 'instock',
            '_stock'                                  => '',
            '_download_expiry'                        => '',
            '_backorders'                             => '',
            '_sold_individually'                      => '',
            '_virtual'                                => '',
            '_downloadable'                           => '',
            '_download_limit'                         => '',
            'rank_math_primary_product_cat'           => '',
            'rank_math_seo_score'                     => '',
            'rank_math_robots'                        => '',
            'rank_math_advanced_robots'               => '',
            'rank_math_dont_show_seo_score'           => '',
            'rank_math_permalink'                     => '',
            'rank_math_twitter_card_type'             => '',
            'rank_math_twitter_enable_image_overlay'  => '',
            'rank_math_twitter_image_overlay'         => '',
            'rank_math_twitter_use_facebook'          => '',
            'rank_math_facebook_image_overlay'        => '',
            'rank_math_facebook_enable_image_overlay' => '',
            'rank_math_rich_snippet'                  => '',
        ];

        $metas = [];
        foreach ($meta_keys as $key) {
            $metas[] = "(" . implode(",", [
                    'post_id'    => $post_id,
                    'meta_key'   => $key,
                    'meta_value' => $meta_values[$key],
                ]) . ")";
        }

        if (!$this->dest_insert_into_bulk("postmeta", $meta_keys, $metas)) {
            echo "$post_id convertion failed ###########\n";
            echo "ERROR: {$this->dest->error}\n";
            return false;
        }
        return true;
    }

    function src_get_products($exception_ids)
    {
        $items = $this->src->select(
            "select * from " . Kernel::env('SRC_DB_PREFIX') . "posts " .
            "where post_status = 'publish' " .
            "and post_type = 'product' " .
            (!empty($exception_ids) ? "and `id` not in (" . implode(',', $exception_ids) . ")" : "")
        );
        foreach ($items as $item) {
            yield $item;
        }
    }

    function main_get_converted_products()
    {
        $items = $this->main_db->select_all(
            "select `src_post_id` from " . Kernel::env('MAIN_DB_PREFIX') . "posts " .
            "where `status` = 'done' " .
            "and post_type = 'product' "
        );

        return empty($items) ? [] : array_column($items, "src_post_id");
    }

    function src_get_meta($post_id, $meta_key)
    {
        $meta = $this->src->select_one(
            "select meta_value from " . Kernel::env('SRC_DB_PREFIX') . "postmeta " .
            "where post_id = '$post_id' " .
            "and meta_key = '$meta_key' ");

        return $meta['meta_value'] ?? null;
    }

    function src_get_image($post_id)
    {
        $img = $this->src->select_one(
            "select * from " . Kernel::env('SRC_DB_PREFIX') . "posts " .
            "where post_parent = '$post_id' " .
            "and post_type = 'attachment' " .
            "ORDER BY `post_date` ");

        return $img ?? null;

    }



//    function get_articles()
//    {
//        $items = $this->src->select(
//            "select * from " . Kernel::env('SRC_DB_PREFIX') . "posts " .
//            "where post_status = 'publish' " .
//            "and post_type = 'article' ");
//        foreach ($items as $item) {
//            yield $item;
//        }
//    }

    function main_insert_product($status, $src_id, $message = "", $dest_id = null)
    {
        $rec = [
            "src_post_id"  => $src_id,
            "dest_post_id" => $dest_id ?? null,
            "post_type"    => 'product',
            "status"       => $status,
            "message"      => $message,
            "created_at"   => date("Y-m-d H:i:s", time()),
            "updated_at"   => date("Y-m-d H:i:s", time()),
        ];

        $keys = implode("`,`", array_keys($rec));
        $values = implode("','", array_values($rec));

        $qry = "insert into " . Kernel::env('MAIN_DB_PREFIX') . "posts " .
            " (`$keys`) " .
            "VALUES ('$values');";

        $ret = $this->main_db->insert($qry);
        return $ret;
    }


    function dest_insert_into($table_name, $rec)
    {
        $keys = implode('`,`', array_keys($rec));
        $values = implode('\',\'', array_values($rec));

        $qry = "insert into " . Kernel::env('DST_DB_PREFIX') . "$table_name " .
            " (`$keys`) " .
            "VALUES ('$values');";

        return $this->dest->insert($qry);
    }

    function dest_insert_into_bulk($table_name, $keys, $values)
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


    function setup()
    {
        $this->main_db->execute("DROP TABLE IF EXISTS `" . Kernel::env('MAIN_DB_PREFIX') . "posts`");
        $this->main_db->execute(
            "CREATE TABLE `" . Kernel::env('MAIN_DB_PREFIX') . "posts` ( " .
            "`id` int not null AUTO_INCREMENT, " .
            "src_post_id int, " .
            "dest_post_id int, " .
            "post_type varchar(100), " .
            "status varchar(100), " .
            "message varchar(500), " .
            "created_at timestamp, " .
            "updated_at timestamp, " .
            " PRIMARY KEY (`id`)" .
            ")"
        );
    }
}
