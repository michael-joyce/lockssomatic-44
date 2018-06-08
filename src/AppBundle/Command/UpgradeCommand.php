<?php

namespace AppBundle\Command;

use AppBundle\Entity\Box;
use AppBundle\Entity\BoxStatus;
use AppBundle\Entity\ContentOwner;
use AppBundle\Entity\ContentProvider;
use AppBundle\Entity\Pln;
use AppBundle\Entity\Plugin;
use AppBundle\Entity\PluginProperty;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Nines\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpgradeCommand extends ContainerAwareCommand {

    /**
     * @var Connection
     */
    private $source;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var array
     */
    private $idMapping;

    /**
     * @var bool
     */
    private $force;

    /**
     * @param Connection $oldEm
     * @param EntityManagerInterface $em
     */
    public function __construct(Connection $oldEm, EntityManagerInterface $em) {
        parent::__construct();
        $this->source = $oldEm;
        $this->em = $em;
        $this->idMapping = array();
        $this->force = false;
    }

    protected function setIdMap($class, $old, $new) {
        $this->idMapping[$class][$old] = $new;
    }

    protected function getIdMap($class, $old, $default = null) {
        if (isset($this->idMapping[$class][$old])) {
            return $this->idMapping[$class][$old];
        }
        return $default;
    }

    /**
     *
     */
    public function configure() {
        $this->setName('lom:upgrade');
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Actually make the database changes.');
    }


    public function findEntity($class, $oldId, $default = null) {
        $newId = $this->getIdMap($class, $oldId);
        if( ! $newId) {
            return $default;
        }
        return $this->em->find($class, $newId);
    }

    /**
     * @param string $table
     * @param callable $callback
     */
    public function upgradeTable($table, $callback) {
        $countQuery = $this->source->query("SELECT count(*) c FROM {$table}");
        $countRow = $countQuery->fetch();
        print "upgrading {$countRow['c']} entities in {$table}.\n";

        $query = $this->source->query("SELECT * FROM {$table}");
        $n = 0;
        print "$n\r";
        while ($row = $query->fetch()) {
            $entity = $callback($row);
            if ($entity) {
                $this->em->persist($entity);
                $this->em->flush($entity);
                $this->setIdMap(get_class($entity), $row['id'], $entity->getId());
                $this->em->detach($entity);
            }
            $n++;
            print "$n\r";
        }
        print "\n";
    }

    public function upgradeUsers() {
        $callback = function($row) {
            if ($row['username'] === 'admin@example.com') {
                return null;
            }
            $entry = new User();
            $entry->setUsername($row['username']);
            $entry->setEmail($row['username']);
            $entry->setEnabled(true);
            $entry->setSalt($row['salt']);
            $entry->setPassword($row['password']);
            $entry->setLastLogin(new DateTime($row['last_login']));
            $entry->setRoles(unserialize($row['roles']));
            $entry->setFullname($row['fullname']);
            $entry->setInstitution($row['institution']);
            return $entry;
        };
        $this->upgradeTable('lom_user', $callback);
    }

    public function upgradeContentOwners() {
        $callback = function($row) {
            $owner = new ContentOwner();
            $owner->setEmailAddress($row['email_address']);
            $owner->setName($row['name']);
            return $owner;
        };
        $this->upgradeTable('content_owners', $callback);
    }

    public function upgradePlns() {
        $callback = function($row) {
            $entity = new Pln();
            $entity->setName($row['name']);
            $entity->setDescription($row['description']);
            $entity->setProperties(unserialize($row['property']));
            $entity->setUsername($row['username']);
            $entity->setPassword($row['password']);

            $query = $this->source->executeQuery('SELECT * FROM keystore WHERE id = :id',
                ['id' => $row['keystore_id']]
            );
            $keystoreRow = $query->fetch();
            $entity->setKeystore($keystoreRow['path']);

            return $entity;
        };
        $this->upgradeTable('plns', $callback);
    }

    public function upgradePlugins() {
        $callback = function($row) {
            $entity = new Plugin();
            $entity->setGenerateManifests(true);
            $entity->setIdentifier($row['identifier']);
            $entity->setName($row['name']);
            $entity->setVersion($row['version']);
            return $entity;
        };
        $this->upgradeTable('plugins', $callback);
    }

    public function upgradePluginProperties() {
        $callback = function($row) {
            $entity = new PluginProperty();
            $entity->setPropertyKey($row['property_key']);
            $entity->setPropertyValue($row['property_value']);
            $entity->setPlugin($this->findEntity(Plugin::class, $row['plugin_id']));
            $entity->setParent($this->findEntity(PluginProperty::class, $row['parent_id']));
            return $entity;
        };
        $this->upgradeTable('plugin_properties', $callback);
    }

    public function upgradeContentProviders() {
        $callback = function($row) {
            $entity = new ContentProvider();
            $entity->setContentOwner($this->findEntity(ContentOwner::class, $row['content_owner_id']));
            $entity->setPln($this->findEntity(Pln::class, $row['pln_id']));
            $entity->setPlugin($this->findEntity(Plugin::class, $row['plugin_id']));
            $entity->setUuid($row['uuid']);
            $entity->setPermissionUrl($row['permissionUrl']);
            $entity->setName($row['name']);
            $entity->setMaxFileSize($row['max_file_size']);
            $entity->setMaxAuSize($row['max_au_size']);
            return $entity;
        };
        $this->upgradeTable('content_providers', $callback);
    }

    public function upgradeBoxes() {
        $callback = function($row) {
            $box = new Box();
            $box->setHostname($row['hostname']);
            $box->setIpAddress($row['ip_address']);
            $box->setProtocol($row['protocol']);
            $box->setPort($row['port']);
            $box->setWebServicePort($row['ws_port']);
            $box->setWebServiceProtocol('http');
            $box->setActive($row['active'] == 1);
            $box->setSendNotifications(false);
            $box->setPln($this->findEntity(Pln::class, $row['pln_id']));
            return $box;
        };
        $this->upgradeTable('boxes', $callback);
    }

    public function upgradeBoxStatus() {
        $callback = function($row) {
            $status = new BoxStatus();
            $status->setBox($this->findEntity(Box::class, $row['box_id']));
            $status->setCreated(new DateTime($row['query_date']));
            $status->setSuccess($row['success'] == 1);
            $status->setErrors($row['errors']);
            return $status;
        };
        $this->upgradeTable('box_status', $callback);
    }

    public function upgradeCacheStatus() {
        $callback = function($row) {
            $status = $this->findEntity(BoxStatus::class, $row['boxstatus_id']);
            $status->setData(unserialize($row['response']));
            $this->em->flush();
            $this->em->detach($status);
            return null;
        };
        $this->upgradeTable('cache_status', $callback);
    }

//    public function upgrade() {
//        $callback = function($row) {
//            $entity = new \stdClass();
//            return $entity;
//        };
//        $this->upgradeTable('', $callback);
//    }

    public function execute(InputInterface $input, OutputInterface $output) {
        if (!$input->getOption('force')) {
            $output->writeln("Will not run without --force.");
            exit;
        }
        $this->upgradeUsers();
        $this->upgradeContentOwners();
        $this->upgradePlns();
        $this->upgradePlugins();
        $this->upgradePluginProperties();
        $this->upgradeContentProviders();
        $this->upgradeBoxes();
        $this->upgradeBoxStatus();
        $this->upgradeCacheStatus();
    }

}
