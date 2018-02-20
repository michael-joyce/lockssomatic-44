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
use Doctrine\ORM\Query;

/**
 * Content queries against the database, via Doctrine.
 */
class ContentRepository extends EntityRepository {
    
    /**
     * Get a query for the content items in an AU.
     *
     * @param Au $au
     *
     * @return Query
     */
    public function auQuery(Au $au) {
        $qb = $this->createQueryBuilder('c');
        $qb->andWhere("c.au = :au");
        $qb->setParameter('au', $au);
        return $qb->getQuery();
    }
    
}
