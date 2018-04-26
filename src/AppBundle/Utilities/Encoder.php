<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Utilities;

/**
 * Encode/decode LOCKSS URL-encoded data.
 *
 * LOCKSS does a percent-encoding for some things. It's very similar to the
 * result or rawurlencode(), but the set of characters encoded is slightly
 * different.
 */
class Encoder {

    /**
     * Encode a string.
     *
     * @param string $string
     *   The string to be encoded.
     *
     * @return string
     *   The result of the encoding. Returns null if $string is null.
     */
    public function encode($string) {
        if($string === null) {
            return null;
        }
        $callback = function ($matches) {
            $char = ord($matches[0]);
            return '%' . strtoupper(sprintf('%02x', $char));
        };

        $encoded = preg_replace_callback('/[^a-zA-Z0-9_* -]/', $callback, $string);
        return str_replace(' ', '+', $encoded);
    }

    /**
     * Decode a string.
     *
     * @param string $string
     *   The string to decode.
     *
     * @return string
     *   The result of decoding.
     */
    public function decode($string) {
        $decoded = str_replace('+', ' ', $string);
        return rawurldecode($decoded);
    }

}
