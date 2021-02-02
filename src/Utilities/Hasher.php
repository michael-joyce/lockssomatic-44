<?php


namespace App\Utilities;


class Hasher {

    /**
     * Read files in 64kb chunks.
     */
    const BLOCK_SIZE = 1024 * 64;

    /**
     * @param $filepath
     * @param $type
     * @return string
     */
    public function hash($filepath, $type) {
        $fh = fopen($filepath, 'rb');
        $context = hash_init($type);
        while(($data = fread($fh, self::BLOCK_SIZE))) {
            hash_update($context, $data);
        }
        return hash_final($context);
    }

}
