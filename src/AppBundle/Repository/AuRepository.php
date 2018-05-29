<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Repository;

use AppBundle\Entity\Au;
use AppBundle\Entity\Deposit;
use Doctrine\ORM\EntityRepository;
use Iterator;

/**
 * AU queries in Doctrine.
 */
class AuRepository extends EntityRepository {

    /**
     * Find an open AU and return it.
     *
     * @param string $auid
     *   The LOCKSSOMatic AUID to search for.
     *
     * @return Au|null
     *   The open AU or null if one cannot be found.
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
     * @param Au $au
     *   The AU to work on.
     *
     * @return int
     *   The AU size in 1000-byte kb.
     */
    public function getAuSize(Au $au) {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('SUM(d.size) as size');
        $qb->from(Deposit::class, 'd');
        $qb->where('d.au = :au');
        $qb->setParameter('au', $au);
        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Count the deposits in an AU.
     *
     * @param Au $au
     *   The AU to work on.
     *
     * @return int
     *   The AU size.
     */
    public function countDeposits(Au $au) {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('COUNT(d.id) as c');
        $qb->from(Deposit::class, 'd');
        $qb->where('d.au = :au');
        $qb->setParameter('au', $au);
        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get a query for the content items in an AU.
     *
     * @param Au $au
     *   The AU to query.
     *
     * @return Iterator|Deposit[]
     *   The iterator for the deposits.
     */
    public function iterateDeposits(Au $au) {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('d');
        $qb->from(Deposit::class, 'd');
        $qb->andWhere("d.au = :au");
        $qb->setParameter('au', $au);
        $iterator = $qb->getQuery()->iterate();
        $iterator->rewind();
        return $iterator;
    }

}
