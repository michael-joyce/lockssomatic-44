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
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Monolog\Logger;

/**
 * Ensure content items in an AU all have the same set of required properties.
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class AuValidator {

    const BATCHSIZE = 100;

    /**
     * Psr\Log compatible logger for the service.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Doctrine entity manager.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Construct the service.
     *
     * @param LoggerInterface $logger
     *   Logger for warnings etc.
     * @param EntityManagerInterface $em
     *   Doctrine instance to persist properties.
     */
    public function __construct(LoggerInterface $logger, EntityManagerInterface $em) {
        $this->logger = $logger;
        $this->em = $em;
    }

    /**
     * Validate the AU content.
     *
     * Checks that every content item in the AU has the same set
     * of definitional configuration parameters, and returns the
     * number of errors.
     *
     * @param Au $au
     *   The AU to check.
     *
     * @return int
     *   The number of errors found.
     *
     * @throws Exception
     */
    public function validate(Au $au) {
        $errors = 0;
        $plugin = $au->getPlugin();
        if (!$plugin) {
            throw new Exception("Cannot validate an AU without a plugin.");
        }
        $definitional = $plugin->getDefinitionalPropertyNames();
        if (!$definitional || count($definitional) === 0) {
            throw new Exception("Cannot validate AU for plugin without definitional properties.");
        }

        $repo = $this->em->getRepository(Content::class);
        $iterator = $repo->auQuery($au);

        $first = $iterator->next()[0];
        $base = [];
        foreach ($definitional as $name) {
            $base[$name] = $first->getProperty($name);
        }

        $i = 0;
        while (($row = $iterator->next()) !== false) {
            $content = $row[0];
            foreach ($definitional as $name) {
                if ($content->getProperty($name) !== $base[$name]) {
                    $errors++;
                }
            }

            $i++;
            if ($i % self::BATCHSIZE === 0) {
                $this->em->clear();
            }
        }
        return $errors;
    }

}
