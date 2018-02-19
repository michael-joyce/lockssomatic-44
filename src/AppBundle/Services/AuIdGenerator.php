<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Services;

use AppBundle\Entity\Au;
use AppBundle\Entity\Content;
use AppBundle\Utilities\Encoder;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Description of AuIdGenerator
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class AuIdGenerator {
    
    private $logger;
    
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }
    
    public function fromContent(Content $content, $lockssAuid = true) {
        $plugin = $content->getPlugin();
        if($plugin === null) {
            return null;
        }
        $pluginId = $plugin->getIdentifier();
        $id = str_replace('.', '|', $pluginId);
        $names = $plugin->getDefinitionalProperties();
        sort($names);
        $encoder = new Encoder();
        $this->logger->warning("generated params: " . print_r($plugin->getGeneratedParams(), true));
        foreach($names as $name) {
            $this->logger->warning("Generating {$name}");
            if( ! $lockssAuid && in_array($name, $plugin->getGeneratedParams())) {
                $this->logger->warning("Skipping.");
                // skip any properties that LOM will generate later.
                continue;
            }
            $value = $content->getProperty($name);
            if( ! $value) {
                throw new Exception("Cannot generate AUID without definitional property {$name}.");
            }
            $id .= '&' . $name . '~' . $encoder->encode($value);
        }
        
        return $id;
    }
    
    /**
     * Sets the AU id based on the first content item in the AU and returns it.
     * 
     * Assumes that the AU properties are already generated.
     * 
     * @param Au $au
     * @return string|null
     */
    public function fromAu(Au $au, $lockssAuid = true) {
        $content = $au->getContent()->first();
        if($content === null) {
            return null;
        }
        $auid = $this->fromContent($content, $lockssAuid);
        return $auid;
    }
    
}
