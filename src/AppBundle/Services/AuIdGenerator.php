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
     * LOM non-definitional configuration parameter directive names.
     * 
     * LOM will ignore these CPDs when building LOM-specific AUids.
     *
     * @var array
     */
    private $nonDefinitionalCpds;
    
    /**
     * Build the service.
     *
     * @param LoggerInterface $logger
     *   Dependency injected logger.
     */
    public function __construct($nonDefinitionalCpds, LoggerInterface $logger) {
        $this->nonDefinitionalCpds = $nonDefinitionalCpds;
        $this->logger = $logger;
    }
    
    public function fromContent(Content $content, $lockssAuid = true) {
        $plugin = $content->getDeposit()->getContentProvider()->getPlugin();
        $pluginId = $plugin->getIdentifier();
        $pluginKey = str_replace('.', '|', $pluginId);
        $auKey = '';
        $propNames = $plugin->getDefinitionalPropertyNames();
        sort($propNames);
        foreach($propNames as $name) {
            
        }
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
        $plugin = $au->getPlugin();
        if ($plugin === null) {
            return null;
        }
        $pluginId = $plugin->getIdentifier();
        $id = str_replace('.', '|', $pluginId);
        $names = $plugin->getDefinitionalPropertyNames();
        sort($names);
        $encoder = new Encoder();
        foreach ($names as $name) {
            $value = $au->getAuPropertyValue($name);
            if (!$value) {
                throw new Exception("Cannot generate AUID without definitional property {$name}.");
            }
            $id .= '&' . $name . '~' . $encoder->encode($value);
        }
        
        return $id;
    }
    
}
