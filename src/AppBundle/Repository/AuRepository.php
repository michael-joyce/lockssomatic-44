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
 * AuRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AuRepository extends EntityRepository {

    /**
     * @param string $auid
     *
     * @return Au|null
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

    public function getAuSize(Au $au) {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('SUM(d.size) as size');
        $qb->from(Deposit::class, 'd');
        $qb->where('d.au = :au');
        $qb->setParameter('au', $au);
        return $qb->getQuery()->getSingleScalarResult();
    }

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
     *
     * @return Iterator|Deposit[]
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
