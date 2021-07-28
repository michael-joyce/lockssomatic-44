<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Repository;

use App\Entity\Deposit;
use App\Entity\Pln;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine stub.
 */
class DepositRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Deposit::class);
    }

    public function indexQuery(Pln $pln) {
        $qb = $this->createQueryBuilder('e');
        $qb->innerJoin('e.au', 'a');
        $qb->where('a.pln = :pln');
        $qb->setParameter('pln', $pln);
        $qb->orderBy('e.id', 'desc');

        return $qb->getQuery();
    }

    public function searchQuery($q, ?Pln $pln = null) {
        $qb = $this->createQueryBuilder('e');
        $qb->addSelect('MATCH(e.uuid, e.url, e.title) AGAINST(:q BOOLEAN) AS HIDDEN score');
        $qb->setParameter('q', $q);
        $qb->andWhere('MATCH(e.uuid, e.url, e.title) AGAINST(:q BOOLEAN) > 0.0');
        $qb->orderBy('score', 'desc');
        if ($pln) {
            $qb->innerJoin('e.au', 'a');
            $qb->andWhere('a.pln = :pln');
            $qb->setParameter('pln', $pln);
        }

        return $qb->getQuery();
    }

    /**
     * Fetch a list of deposits to check.
     *
     * @param ?array $plns
     * @param ?array $uuids
     *
     * @return Query
     */
    public function checkQuery(?array $plns = null, ?array $uuids = null, bool $all = false, bool $count = false) {
        $yesterday = new DateTimeImmutable();
        $yesterday->modify('-1 day');

        $qb = $this->createQueryBuilder('d');
        if ($count) {
            $qb->select('count(1)');
        } else {
            $qb->orderBy('d.checked', 'DESC');
        }
        if ( ! $all) {
            $qb->andWhere('d.agreement IS NULL or d.agreement < 1.0');
            $qb->andWhere('d.checked IS NULL OR d.checked < :yesterday');
            $qb->setParameter('yesterday', $yesterday);
        }
        if ($plns) {
            $qb->andWhere('d.pln in :plns');
            $qb->setParameter('plns', $plns);
        }
        if ($uuids) {
            $qb->andWhere('d.uuid in :uuids');
            $qb->setParameter('uuids', $uuids);
        }

        return $qb->getQuery();
    }
}
