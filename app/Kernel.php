<?php


namespace App;


class Kernel
{
    public function run(){
        echo 'WP Converter is ready.'."\n";

        $src  = new DB('localhost',3306,'mirold','2m%Jwe59','mirDBold');
        $dest = new DB('localhost',3306,'mirDBUsrd','#5mlJk23zFyl30~9','mirDBsad');
    }
}