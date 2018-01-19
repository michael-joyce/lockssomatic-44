<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Services;

/**
 * Description of Encoder
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class Encoder {
    
    /**
     * LOCKSS does a percent-encoding for some things. It's very similar to the
     * result or rawurlencode(), but the set of characters encoded is slightly 
     * different.
     * 
     * @param string $string
     * @return string
     */
    public function encode($string) {
        $callback = function ($matches) {
            $char = ord($matches[0]);
            return '%'.strtoupper(sprintf('%02x', $char));
        };

        $encoded = preg_replace_callback('/[^a-zA-Z0-9_* -]/', $callback, $string);
        return str_replace(' ', '+', $encoded);
    }
    
    /**
     * The encoding is compatible with URL encoding.
     * 
     * @param type $string
     * @return type
     */
    public function decode($string) {
        $decoded = str_replace('+', ' ', $string);
        return rawurldecode($decoded);
    }
    
}
