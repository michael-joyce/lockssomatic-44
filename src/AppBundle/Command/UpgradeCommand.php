<?php

namespace AppBundle\Command;

use AppBundle\Entity\Au;
use AppBundle\Entity\AuProperty;
use AppBundle\Entity\AuStatus;
use AppBundle\Entity\Box;
use AppBundle\Entity\BoxStatus;
use AppBundle\Entity\ContentOwner;
use AppBundle\Entity\ContentProvider;
use AppBundle\Entity\Deposit;
use AppBundle\Entity\DepositStatus;
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

    public function upgradeAus() {
        $callback = function($row) {
            $au = new Au();
            $au->setPln($this->findEntity(Pln::class, $row['pln_id']));
            $au->setContentProvider($this->findEntity(ContentProvider::class, $row['contentprovider_id']));
            $au->setPlugin($this->findEntity(Plugin::class, $row['plugin_id']));
            $au->setAuid($row['auid']);
            $au->setComment($row['comment']);
            $au->setOpen(false);
            return $au;
        };
        $this->upgradeTable('aus', $callback);
    }

    public function upgradeAuProperties() {
        $callback = function($row) {
            $property = new AuProperty();
            $property->setParent($this->findEntity(AuProperty::class, $row['parent_id']));
            $property->setAu($this->findEntity(Au::class, $row['au_id']));
            $property->setPropertyKey($row['property_key']);
            $property->setPropertyValue($row['property_value']);
            return $property;
        };
        $this->upgradeTable('au_properties', $callback);
    }

    public function upgradeAuStatus() {
        $callback = function($row) {
            $status = new AuStatus();
            $status->setAu($this->findEntity(Au::class, $row['au_id']));
            $status->setCreated(new DateTime($row['query_date']));
            $status->setStatus($row['status']);
            $status->setErrors($row['errors']);
            return $status;
        };
        $this->upgradeTable('au_status', $callback);
    }

    public function findContent($depositId) {
        static $query = null;
        if( ! $query) {
            $query = $this->source->prepare('SELECT * FROM content WHERE deposit_id = :id');
        }
        $query->execute(array('id' => $depositId));
        $row = $query->fetch();
        $query->closeCursor();
        return $row;
    }

    public function findContentProperties($contentId) {
        static $query = null;
        if( ! $query) {
            $query = $this->source->prepare('SELECT * FROM content_properties WHERE content_id = :id');
        }
        $query->execute(array('id' => $contentId));
        $properties = array();
        while($row = $query->fetch()) {
            $properties[$row['property_key']] = $row['property_value'];
        }
        $query->closeCursor();
        return $properties;
    }

    public function upgradeDeposits() {
        $callback = function($row) {
            $deposit = new Deposit();
            $deposit->setContentProvider($this->findEntity(ContentProvider::class, $row['content_provider_id']));
            $deposit->setUser($this->findEntity(User::class, $row['user_id']));
            $deposit->setUuid($row['uuid']);
            $deposit->setTitle($row['title']);
            $deposit->setCreated(new DateTime($row['date_deposited']));
            $deposit->setAgreement($row['agreement']);

            $contentRow = $this->findContent($row['id']);
            $deposit->setAu($this->findEntity(Au::class, $contentRow['au_id']));
            $deposit->setUrl($contentRow['url']);
            $deposit->setSize($contentRow['size']);
            $deposit->setChecksumType($contentRow['checksum_type']);
            $deposit->setChecksumValue($contentRow['checksum_value']);

            $deposit->setProperties($this->findContentProperties($contentRow['id']));
            return $deposit;
        };
        $this->upgradeTable('deposits', $callback);
    }

    public function upgradeDepositStatus() {
        $callback = function($row) {
            $status = new DepositStatus();
            $status->setDeposit($this->findEntity(Deposit::class, $row['deposit_id']));
            $status->setAgreement($row['agreement']);
            $status->setCreated(new DateTime($row['query_date']));
            $status->setStatus($row['status']);
            return $status;
        };
        $this->upgradeTable('deposit_status', $callback);
    }

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
        $this->upgradeAus();
        $this->upgradeAuProperties();
        $this->upgradeAuStatus();
        $this->upgradeDeposits();
        $this->upgradeDepositStatus();
    }

}
