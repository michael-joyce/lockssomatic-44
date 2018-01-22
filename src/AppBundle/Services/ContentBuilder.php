<?php

namespace AppBundle\Services;

use AppBundle\Entity\Content;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Symfony\Bridge\Monolog\Logger;

/**
 * Build a content object.
 */
class ContentBuilder {

    /**
     * Psr/Logger compatible logger.
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
     * Build a content item from some XML.
     *
     * Persists, but does not flush, the object to the database.
     *
     * @param SimpleXMLElement $xml
     *   The XML data.
     *
     * @return Content
     *   The constructed content object.
     */
    public function fromSimpleXml(SimpleXMLElement $xml) {
        $content = new Content();
        $content->setSize((string) $xml->attributes()->size);
        $content->setChecksumType((string) $xml->attributes()->checksumType);
        $content->setChecksumValue((string) $xml->attributes()->checksumValue);
        $content->setUrl(trim((string) $xml));
        $content->setDateDeposited();
        $content->setProperty('journalTitle', (string) $xml->attributes('pkp', true)->journalTitle);
        $content->setProperty('publisher', (string) $xml->attributes('pkp', true)->publisher);
        $content->setTitle((string) $xml->attributes('pkp', true)->journalTitle);

        foreach ($xml->xpath('lom:property') as $node) {
            $content->setProperty((string) $node->attributes()->name, (string) $node->attributes()->value);
        }

        if ($this->em !== null) {
            $this->em->persist($content);
        }

        return $content;
    }

    /**
     * Build a content item from an array, probably from a CSV file.
     *
     * The $record requires size, checksum type, checksum value, url. Title
     * is optional and anything required by the relevant LOCKSS plugin is
     * also required.
     *
     * @param array $record
     *   The data to build the object.
     *
     * @return Content
     *   The built object.
     */
    public function fromArray(array $record) {
        $content = new Content();
        $content->setSize($record['size']);
        $content->setChecksumType($record['checksum type']);
        $content->setChecksumValue($record['checksum value']);
        $content->setUrl($record['url']);
        if (array_key_exists('title', $record)) {
            $content->setTitle($record['title']);
        } else {
            $content->setTitle('Generated Title');
        }
        $content->setDateDeposited();

        foreach ($record as $key => $value) {
            $content->setProperty($key, $value);
        }

        if ($this->em !== null) {
            $this->em->persist($content);
        }
        return $content;
    }

}
