<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Utilities;

/**
 * Encode/decode LOCKSS URL-encoded data.
 *
 * LOCKSS does a percent-encoding for some things. It's very similar to the
 * result of rawurlencode(), but the set of characters encoded is slightly
 * different.
 */
class Encoder
{
    /**
     * Encode a string.
     *
     * @param string $string
     *
     * @return string
     */
    public function encode($string) {
        if (null === $string) {
            return '';
        }
        $callback = function ($matches) {
            $char = ord($matches[0]);

            return '%' . mb_strtoupper(sprintf('%02x', $char));
        };

        $encoded = preg_replace_callback('/[^a-zA-Z0-9_* -]/', $callback, $string);

        return str_replace(' ', '+', $encoded);
    }

    /**
     * Decode a string.
     *
     * @param string $string
     *
     * @return string
     */
    public function decode($string) {
        $decoded = str_replace('+', ' ', $string);

        return rawurldecode($decoded);
    }
}
