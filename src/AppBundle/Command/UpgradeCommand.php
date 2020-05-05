<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

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
     * Doctrine database connection for the old database.
     *
     * @var Connection
     */
    private $source;

    /**
     * Entity manager connected to the new database.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Mapping of old IDs to new IDs based on class names.
     *
     * Something like this if the old user ID was three and the new one was 5.
     * $idMapping[User::class][3] = 5
     *
     * @var array
     */
    private $idMapping;

    /**
     * If true the changes will be flushed to the new database.
     *
     * @var bool
     */
    private $force;

    /**
     * Construct the command instance.
     *
     * $oldEm is a Doctrine connection configured for the previous version
     * of the database. $em is an entity manager configured for the current
     * version.
     *
     * This file and the corresponding configuration should both be removed
     * after the upgrade is complete.
     *
     * see app/config/config.yml for examples of the configuration.
     * see app/config/services.yml to configure the dependency injection.
     */
    public function __construct(Connection $oldEm, EntityManagerInterface $em) {
        parent::__construct();
        $this->source = $oldEm;
        $this->em = $em;
        $this->idMapping = [];
        $this->force = false;
    }

    /**
     * Map an old database ID to a new one.
     *
     * @param string $class
     * @param int $old
     * @param int $new
     */
    protected function setIdMap($class, $old, $new) : void {
        $this->idMapping[$class][$old] = $new;
    }

    /**
     * Get the new database ID for a $class.
     *
     * @param string $class
     * @param int $old
     * @param int $default
     *
     * @return null|int
     */
    protected function getIdMap($class, $old, $default = null) {
        if (isset($this->idMapping[$class][$old])) {
            return $this->idMapping[$class][$old];
        }

        return $default;
    }

    public function configure() : void {
        $this->setName('lom:upgrade');
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Actually make the database changes.');
    }

    /**
     * Find a new entity based on its class and old ID.
     *
     * @param string $class
     * @param int $oldId
     * @param mixed $default
     *
     * @return mixed
     */
    public function findEntity($class, $oldId, $default = null) {
        $newId = $this->getIdMap($class, $oldId);
        if ( ! $newId) {
            return $default;
        }

        return $this->em->find($class, $newId);
    }

    /**
     * Perform an upgrade on one table.
     *
     * Processes each row of the table with $callback. If $callback returns an
     * object it is persisted and flushed, and the old ID is mapped to the new one.
     *
     * @param string $table
     */
    public function upgradeTable($table, callable $callback) : void {
        $countQuery = $this->source->query("SELECT count(*) c FROM {$table}");
        $countRow = $countQuery->fetch();
        echo "upgrading {$countRow['c']} entities in {$table}.\n";

        $query = $this->source->query("SELECT * FROM {$table}");
        $n = 0;
        echo "{$n}\r";
        while ($row = $query->fetch()) {
            $entity = $callback($row);
            if ($entity) {
                $this->em->persist($entity);
                $this->em->flush($entity);
                $this->setIdMap(get_class($entity), $row['id'], $entity->getId());
                $this->em->detach($entity);
            }
            $n++;
            echo "{$n}\r";
        }
        echo "\n";
    }

    /**
     * Upgrade the users table.
     */
    public function upgradeUsers() : void {
        $callback = function ($row) {
            if ('admin@example.com' === $row['username']) {
                return;
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

    /**
     * Upgrade the content owners table.
     */
    public function upgradeContentOwners() : void {
        $callback = function ($row) {
            $owner = new ContentOwner();
            $owner->setEmailAddress($row['email_address']);
            $owner->setName($row['name']);

            return $owner;
        };
        $this->upgradeTable('content_owners', $callback);
    }

    /**
     * Upgrade the pln table.
     */
    public function upgradePlns() : void {
        $callback = function ($row) {
            $entity = new Pln();
            $entity->setName($row['name']);
            $entity->setDescription($row['description']);
            $entity->setProperties(unserialize($row['property']));
            $entity->setUsername($row['username']);
            $entity->setPassword($row['password']);

            $query = $this->source->executeQuery(
                'SELECT * FROM keystore WHERE id = :id',
                ['id' => $row['keystore_id']]
            );
            $keystoreRow = $query->fetch();
            $entity->setKeystore($keystoreRow['path']);

            return $entity;
        };
        $this->upgradeTable('plns', $callback);
    }

    /**
     * Upgrade the plugin table.
     */
    public function upgradePlugins() : void {
        $callback = function ($row) {
            $entity = new Plugin();
            $entity->setGenerateManifests(true);
            $entity->setIdentifier($row['identifier']);
            $entity->setName($row['name']);
            $entity->setVersion($row['version']);

            return $entity;
        };
        $this->upgradeTable('plugins', $callback);
    }

    /**
     * Upgrade the plugin property table.
     *
     * Requires the plugins updated first.
     */
    public function upgradePluginProperties() : void {
        $callback = function ($row) {
            $entity = new PluginProperty();
            $entity->setPropertyKey($row['property_key']);
            $entity->setPropertyValue($row['property_value']);
            $entity->setPlugin($this->findEntity(Plugin::class, $row['plugin_id']));
            $entity->setParent($this->findEntity(PluginProperty::class, $row['parent_id']));

            return $entity;
        };
        $this->upgradeTable('plugin_properties', $callback);
    }

    /**
     * Upgrade the content provider table.
     *
     * Requires the pln and plugin tables upgraded first.
     */
    public function upgradeContentProviders() : void {
        $callback = function ($row) {
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

    /**
     * Upgrade the box table.
     *
     * Requires the pln table updated first.
     */
    public function upgradeBoxes() : void {
        $callback = function ($row) {
            $box = new Box();
            $box->setHostname($row['hostname']);
            $box->setIpAddress($row['ip_address']);
            $box->setProtocol($row['protocol']);
            $box->setPort($row['port']);
            $box->setWebServicePort($row['ws_port']);
            $box->setWebServiceProtocol('http');
            $box->setActive(1 === $row['active']);
            $box->setSendNotifications(false);
            $box->setPln($this->findEntity(Pln::class, $row['pln_id']));

            return $box;
        };
        $this->upgradeTable('boxes', $callback);
    }

    /**
     * Upgrade the box status table.
     *
     * Requires the box table upgraded first.
     */
    public function upgradeBoxStatus() : void {
        $callback = function ($row) {
            $status = new BoxStatus();
            $status->setBox($this->findEntity(Box::class, $row['box_id']));
            $status->setCreated(new DateTime($row['query_date']));
            $status->setSuccess(1 === $row['success']);
            $status->setErrors($row['errors']);

            return $status;
        };
        $this->upgradeTable('box_status', $callback);
    }

    /**
     * Upgrade the cache status table by moving the statuses to box status.
     *
     * Requires the box status table upgraded first.
     */
    public function upgradeCacheStatus() : void {
        $callback = function ($row) {
            $status = $this->findEntity(BoxStatus::class, $row['boxstatus_id']);
            $status->setData(unserialize($row['response']));
            $this->em->flush();
            $this->em->detach($status);
        };
        $this->upgradeTable('cache_status', $callback);
    }

    /**
     * Upgrade the Au table.
     *
     * Requires the pln, content provider, and plugin tables upgraded first.
     */
    public function upgradeAus() : void {
        $callback = function ($row) {
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

    /**
     * Upgrade the AU property table.
     *
     * Requires the Au table upgraded first.
     */
    public function upgradeAuProperties() : void {
        $callback = function ($row) {
            $property = new AuProperty();
            $property->setParent($this->findEntity(AuProperty::class, $row['parent_id']));
            $property->setAu($this->findEntity(Au::class, $row['au_id']));
            $property->setPropertyKey($row['property_key']);
            $property->setPropertyValue($row['property_value']);

            return $property;
        };
        $this->upgradeTable('au_properties', $callback);
    }

    /**
     * Upgrade the AU status table.
     *
     * Requires the Au table upgraded first.
     */
    public function upgradeAuStatus() : void {
        $callback = function ($row) {
            $status = new AuStatus();
            $status->setAu($this->findEntity(Au::class, $row['au_id']));
            $status->setCreated(new DateTime($row['query_date']));
            $status->setStatus($row['status']);
            $status->setErrors($row['errors']);

            return $status;
        };
        $this->upgradeTable('au_status', $callback);
    }

    /**
     * Find a row from the content table.
     *
     * @param int $depositId
     *
     * @staticvar Statement $query
     *
     * @return array
     */
    public function findContent($depositId) {
        static $query = null;
        if ( ! $query) {
            // only initialize the query once. It can be reused.
            $query = $this->source->prepare('SELECT * FROM content WHERE deposit_id = :id');
        }
        $query->execute(['id' => $depositId]);
        $row = $query->fetch();
        $query->closeCursor();

        return $row;
    }

    /**
     * Find the properties associated with a content row.
     *
     * @param int $contentId
     *
     * @staticvar type $query
     *
     * @return array
     */
    public function findContentProperties($contentId) {
        static $query = null;
        if ( ! $query) {
            // only initialize the query once. It can be reused.
            $query = $this->source->prepare('SELECT * FROM content_properties WHERE content_id = :id');
        }
        $query->execute(['id' => $contentId]);
        $properties = [];
        while ($row = $query->fetch()) {
            $properties[$row['property_key']] = $row['property_value'];
        }
        $query->closeCursor();

        return $properties;
    }

    /**
     * Upgrade the deposit table.
     *
     * Requires content provider, au, user tables upgraded first.
     *
     * Moves all content and content items into the appropriate deposit.
     */
    public function upgradeDeposits() : void {
        $callback = function ($row) {
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

    /**
     * Upgrade the deposit status table.
     *
     * Requires the depost table upgraded first.
     */
    public function upgradeDepositStatus() : void {
        $callback = function ($row) {
            $status = new DepositStatus();
            $status->setDeposit($this->findEntity(Deposit::class, $row['deposit_id']));
            $status->setAgreement($row['agreement']);
            $status->setCreated(new DateTime($row['query_date']));
            $status->setStatus($row['status']);

            return $status;
        };
        $this->upgradeTable('deposit_status', $callback);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output) : void {
        if ( ! $input->getOption('force')) {
            $output->writeln('Will not run without --force.');
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
