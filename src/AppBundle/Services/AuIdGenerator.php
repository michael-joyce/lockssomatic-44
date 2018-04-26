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
 * Description of AuIdGenerator.
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class AuIdGenerator {
    
    /**
     * Logger for logging.
     *
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * Build the service.
     *
     * @param LoggerInterface $logger
     *   Dependency injected logger.
     */
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }
    
    public function fromContent(Content $content, $lockssAuid = true) {
        $encoder = new Encoder();
        $plugin = $content->getPlugin();
        $pluginId = $plugin->getIdentifier();
        $pluginKey = str_replace('.', '|', $pluginId);
        $auKey = '';
        $propNames = $plugin->getDefinitionalPropertyNames();
        sort($propNames);
        foreach($propNames as $name) {
            if( ! $lockssAuid 
                    && in_array($name, $plugin->getGeneratedParams())) {
                continue;
            }
            $value = $encoder->encode($content->getProperty($name));
            if (!$value) {
                throw new Exception("Cannot generate AUID without definitional property {$name}.");
            }
            $auKey .= "&{$name}~{$value}";
        }
        $id = $pluginKey . $auKey;
        return $id;
    }
    
    /**
     * Sets the AU id based on the first content item in the AU and returns it.
     *
     * Assumes that the AU properties are already generated.
     *
     * @param Au $au
     *   Archival unit for generating the AUID.
     * @param bool $lockssAuid
     *   If true, then all CPDs will be included.
     *
     * @return string|null
     *   The generated AUID.
     */
    public function fromAu(Au $au, $lockssAuid = true) {
        if($au->getContent()->count() === 0) {
            dump("no content.");
            return;
        }
        $plugin = $au->getPlugin();
        if ($plugin === null) {
            dump("no plugin.");
            return null;
        }
        return $this->fromContent($au->getContent()->first(), $lockssAuid);
    }
    
}
