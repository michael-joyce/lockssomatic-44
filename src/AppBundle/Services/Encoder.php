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
    
    public function encode($string) {
        $callback = function ($matches) {
            $char = ord($matches[0]);
            return '%'.strtoupper(sprintf('%02x', $char));
        };

        return preg_replace_callback('/[^-_*a-zA-Z0-9]/', $callback, $string);
    }
    
    public function decode($string) {
        $callback = function ($matches) {
            $char = chr(hexdec($matches[0]));
            return $char;
        };

        return preg_replace_callback('/%([:xdigit:]{2})/', $callback, $string);
    }
    
}
