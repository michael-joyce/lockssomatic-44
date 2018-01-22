<?php

namespace AppBundle\Services;

use AppBundle\Entity\Plugin;
use AppBundle\Entity\PluginProperty;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use SimpleXMLElement;
use ZipArchive;

/**
 * Import plugin configuration from a Java .jar file.
 */
class PluginImporter {

    /**
     * Allowed plugin file mime types.
     */
    const MIMETYPES = array(
        'application/java-archive',
        'application/zip',
    );
    
    /**
     * Location of the manifest file inside a .jar file.
     */
    const MANIFEST = 'META-INF/MANIFEST.MF';
   
    /**
     * Name of the manifest key that identifes a lockss plugin xml file.
     */
    const PLUGIN_KEY = 'lockss-plugin';

    /**
     * Names of the prop strings which should be imported.
     */
    const PROP_STRINGS = array(
        'au_name',
        'plugin_crawl_type',
        'plugin_identifier',
        'plugin_name',
        'plugin_publishing_platform',
        'plugin_status',
        'plugin_version',
        'plugin_parent',
        'required_daemon_version',
    );

    /**
     * List of the property lists which should be imported.
     */
    const PROP_LISTS = array(
        'au_permission_url',
        'au_crawlrules',
        'au_start_url',
    );
    
    /**
     * Property trees to import from a plugin's XML.
     */
    const PROP_TREES = array(
        'plugin_config_props',
    );

    /**
     * Doctrine instance.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Set up the service.
     *
     * @param EntityManagerInterface $em
     *   Doctrine manager instance.
     */
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    /**
     * Get the manifest data from the archive.
     *
     * @param ZipArchive $zipArchive
     *   Archive with the data.
     *
     * @return array
     *   Manifest data, each stanza as an array.
     */
    public function getManifest(ZipArchive $zipArchive) {
        $raw = $zipArchive->getFromName(self::MANIFEST);
        $data = preg_replace('/\r\n/', "\n", $raw);
        $manifest = $this->parseManifest($data);
        print_r($manifest);
        return $manifest;
    }

    /**
     * Fidn the plugin XML file entries in a manifest.
     *
     * @param array $manifest
     *   Array of manifest stanzas.
     *
     * @return array
     *   List of plugin XML files.
     */
    public function findPluginEntries(array $manifest) {
        $entries = [];
        foreach ($manifest as $section) {
            if (isset($section[self::PLUGIN_KEY]) && $section[self::PLUGIN_KEY] === 'true') {
                // The comparison above must be string, not boolean.
                $entries[] = $section['name'];
            }
        }
        return $entries;
    }

    /**
     * Get the plugin XML from the archive at the $entry path.
     *
     * @param ZipArchive $zipArchive
     *   Archive containing the XML.
     * @param string $entry
     *   Location of the XML.
     *
     * @return SimpleXMLElement
     *   Constructed XML object
     *
     * @throws Exception
     */
    public function getPluginXml(ZipArchive $zipArchive, $entry) {
        $raw = $zipArchive->getFromName($entry);
        $xml = simplexml_load_string($raw);
        if (!$xml) {
            throw new Exception("Cannot read plugin xml description. " . $zipArchive->getStatusString());
        }
        return $xml;
    }

    /**
     * Parse a manifest string.
     *
     * @param string $raw
     *   Raw manifest data.
     *
     * @return array
     *   Parsed manifest data.
     */
    public function parseManifest($raw) {
        $manifest = preg_replace('/\r\n/', "\n", $raw);
        $sections = [];

        $blocks = preg_split('/\n\s*\n/s', $manifest);
        foreach ($blocks as $block) {
            if (ctype_space($block)) {
                continue;
            }
            $block = preg_replace("/\n\s(\S)/", '\1', $block);
            $keys = [];
            $lines = preg_split('/\n/', $block);
            foreach ($lines as $line) {
                if (!$line) {
                    continue;
                }
                list($k, $v) = preg_split('/\s*:\s*/', $line);
                $keys[mb_convert_case($k, MB_CASE_LOWER)] = $v;
            }
            if (count($keys) > 0) {
                $sections[] = $keys;
            }
        }
        return $sections;
    }

    /**
     * Find a property string in a LOCKSS plugin.xml file.
     *
     * @param SimpleXMLElement $xml
     *   XML object containing the data.
     * @param string $propName
     *   Property to find.
     *
     * @return string|null
     *   The data found.
     *
     * @throws Exception
     */
    public function findXmlPropString(SimpleXMLElement $xml, $propName) {
        $data = $xml->xpath("//entry[string[1]/text() = '{$propName}']/string[2]");
        if (count($data) === 1) {
            return (string) $data[0];
        }
        if (count($data) === 0) {
            return null;
        }
        throw new Exception('Too many entry elements for property string ' . $propName);
    }

    /**
     * Find a list element in a LOCKSS plugin.xml file.
     *
     * @param SimpleXMLElement $xml
     *   The XML object containing the data.
     * @param string $propName
     *   Name of the property to extract.
     *
     * @return SimpleXMLElement|null
     *   Extracted XML data.
     *
     * @throws Exception
     */
    public function findXmlPropElement(SimpleXMLElement $xml, $propName) {
        $data = $xml->xpath("//entry[string[1]/text() = '{$propName}']/list");
        if (count($data) === 1) {
            return $data[0];
        }
        if (count($data) === 0) {
            return null;
        }
        throw new Exception('Too many entry elements for property element' . $propName);
    }

    /**
     * Import data from $value as children of $property.
     *
     * @param PluginProperty $property
     *   Parent property.
     * @param SimpleXMLElement $value
     *   XML value to import.
     *
     * @return PluginProperty
     *   The constructed and persisted property.
     */
    public function importChildren(PluginProperty $property, SimpleXMLElement $value) {
        $childProperty = new PluginProperty();
        $childProperty->setParent($property);
        $property->addChild($childProperty);
        $childProperty->setPlugin($property->getPlugin());
        $property->getPlugin()->addPluginProperty($childProperty);
        $childProperty->setPropertyKey($value->getName());
        $this->em->persist($childProperty);

        foreach ($value->children() as $key => $value) {
            $leaf = new PluginProperty();
            $leaf->setParent($childProperty);
            $childProperty->addChild($leaf);
            $leaf->setPlugin($property->getPlugin());
            $property->getPlugin()->addPluginProperty($leaf);
            $leaf->setPropertyKey($key);
            $leaf->setPropertyValue((string) $value);
            $this->em->persist($leaf);
        }
        return $childProperty;
    }

    /**
     * Build a new plugin configuration tree.
     *
     * Import plugin configuration data from the $name element in $value, as
     * properties of $plugin.
     *
     * @param Plugin $plugin
     *   Plugin for which the configuration data is being imported.
     * @param string $name
     *   Name of the property to import.
     * @param SimpleXMLElement $value
     *   Data to import.
     *
     * @return PluginProperty
     *   Constructed property, assigned to $plugin.
     */
    public function newPluginConfig(Plugin $plugin, $name, SimpleXMLElement $value) {
        $property = new PluginProperty();
        $property->setPlugin($plugin);
        $plugin->addPluginProperty($property);
        $property->setPropertyKey($name);
        $this->em->persist($property);
        foreach ($value->children() as $child) {
            $this->importChildren($property, $child);
        }
        return $property;
    }

    /**
     * Generate and persist a new plugin property object.
     *
     * @param Plugin $plugin
     *   Plugin for the new property.
     * @param string $name
     *   Name of the new property.
     * @param SimpleXMLElement|string $value
     *   Data to add to the property.
     *
     * @return PluginProperty
     *   Constructed property.
     *
     * @throws Exception
     */
    public function newPluginProperty(Plugin $plugin, $name, $value = null) {
        $property = new PluginProperty();
        $property->setPlugin($plugin);
        $plugin->addPluginProperty($property);
        $property->setPropertyKey($name);
        $this->em->persist($property);
        if ($value === null) {
            return $property;
        }
        if (is_string($value)) {
            $property->setPropertyValue($value);
            return $property;
        }
        switch ($value->getName()) {
            // This is the name of the XML element defining the property.
            case 'string':
                $property->setPropertyValue((string) $value);
                break;

            case 'list':
                $values = array();
                foreach ($value->children() as $child) {
                    $values[] = (string) $child;
                }
                $property->setPropertyValue($values);
                break;

            default:
                throw new Exception("Cannot import simple property {$name}.");
        }
        return $property;
    }

    /**
     * Import the data from the plugin.
     *
     * Does not create content owners for the plugins, that's handled by the
     * titledb import command.
     *
     * @param Plugin $plugin
     *   Plugin for the properties to import to.
     * @param SimpleXMLElement $xml
     *   Data to import.
     */
    public function addProperties(Plugin $plugin, SimpleXMLElement $xml) {
        foreach (self::PROP_STRINGS as $propName) {
            $value = $this->findXmlPropString($xml, $propName);
            $this->newPluginProperty($plugin, $propName, $value);
        }

        foreach (self::PROP_LISTS as $propName) {
            $value = $this->findXmlPropElement($xml, $propName);
            $this->newPluginProperty($plugin, $propName, $value);
        }

        foreach (self::PROP_TREES as $propName) {
            $value = $this->findXmlPropElement($xml, $propName);
            $this->newPluginConfig($plugin, $propName, $value);
        }
    }
    
    /**
     * Build a plugin entity from the XML data and return it.
     *
     * @param SimpleXMLElement $xml
     *   Data to use to construct the plugin.
     *
     * @return Plugin
     *   The constructed, persisted plugin.
     *
     * @throws Exception
     */
    public function buildPlugin(SimpleXMLElement $xml) {
        $pluginRepo = $this->em->getRepository(Plugin::class);
        $pluginName = $this->findXmlPropString($xml, 'plugin_name');
        $pluginId = $this->findXmlPropString($xml, 'plugin_identifier');

        $pluginVersion = $this->findXmlPropString($xml, 'plugin_version');
        if ($pluginVersion === null || $pluginVersion === '') {
            throw new Exception("Plugin {$pluginId} does not have a plugin_version element in its XML configuration.");
        }

        if ($pluginRepo->findOneBy(array('identifier' => $pluginId, 'version' => $pluginVersion)) !== null) {
            throw new Exception("Plugin {$pluginId} version {$pluginVersion} has already been imported.");
        }

        $plugin = new Plugin();
        $plugin->setName($pluginName);
        $plugin->setIdentifier($pluginId);
        $plugin->setVersion($pluginVersion);
        $this->addProperties($plugin, $xml);
        $this->em->persist($plugin);
        return $plugin;
    }

    /**
     * Import the plugin data from a zip archive.
     *
     * @param ZipArchive $zip
     *   Archive to import.
     * @param bool $flush
     *   If true, flush the plugin to the database.
     *
     * @return Plugin
     *   The constructed and populated plugin.
     *
     * @throws Exception
     */
    public function import(ZipArchive $zip, $flush = true) {
        $raw = $zip->getFromName(self::MANIFEST);
        $manifest = $this->parseManifest(preg_replace('/\r\n/', "\n", $raw));
        $entries = $this->findPluginEntries($manifest);
        if (count($entries) === 0) {
            throw new Exception("No LOCKSS entries found in manifest.");
        }
        if (count($entries) > 1) {
            throw new Exception("Too many LOCKSS entries in plugin manifest.");
        }
        $xml = $this->getPluginXml($zip, $entries[0]);
        $plugin = $this->buildPlugin($xml);
        if ($flush) {
            $this->em->flush();
        }
        return $plugin;
    }

}
