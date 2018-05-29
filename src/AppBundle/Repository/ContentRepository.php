<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Repository;

use AppBundle\Entity\Au;
use Doctrine\ORM\EntityRepository;
use Iterator;

/**
 * Content queries against the database, via Doctrine.
 *
 * @todo should be unused. remove.
 */
class ContentRepository extends EntityRepository {

    /**
     * Get a query for the content items in an AU.
     *
     * @param Au $au
     *   The au to query.
     *
     * @return Iterator
     *   Returns an iterator to walk the content items.
     */
    public function auQuery(Au $au) {
        $qb = $this->createQueryBuilder('c');
        $qb->andWhere("c.au = :au");
        $qb->setParameter('au', $au);
        return $qb->getQuery()->iterate();
    }

}
