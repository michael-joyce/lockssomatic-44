<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Repository;

use App\Entity\Au;
use App\Entity\Deposit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Generator;

/**
 * AU queries in Doctrine.
 */
class AuRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Au::class);
    }

    /**
     * Find an open AU and return it.
     *
     * @param string $auid
     *
     * @return null|Au
     *                 The open AU or null if one cannot be found.
     */
    public function findOpenAu($auid) {
        $qb = $this->createQueryBuilder('au');
        $qb->andWhere('au.auid = :auid');
        $qb->setParameter('auid', $auid);
        $qb->andWhere('au.open = true');
        $qb->orderBy('au.id', 'ASC');
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Calculate the size of an AU.
     *
     * @return int
     */
    public function getAuSize(Au $au) {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('SUM(d.size) as size');
        $qb->from(Deposit::class, 'd');
        $qb->where('d.au = :au');
        $qb->setParameter('au', $au);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Count the deposits in an AU.
     *
     * @return int
     */
    public function countDeposits(Au $au) {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('COUNT(d.id) as c');
        $qb->from(Deposit::class, 'd');
        $qb->where('d.au = :au');
        $qb->setParameter('au', $au);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get a query for the deposits in an AU.
     *
     * You should probably use $query->iterate() or paginate the list of
     * deposits - it can grow very large.
     *
     * @return Query
     */
    public function queryDeposits(Au $au) {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('d');
        $qb->from(Deposit::class, 'd');
        $qb->andWhere('d.au = :au');
        $qb->setParameter('au', $au);

        return $qb->getQuery();
    }

    /**
     * Get a query for the content items in an AU.
     *
     * @return Deposit[]|Generator
     *                             The iterator for the deposits.
     */
    public function iterateDeposits(Au $au) {
        $query = $this->queryDeposits($au);
        $iterator = $query->iterate();

        foreach ($iterator as $row) {
            yield $row[0];
        }
    }
}
