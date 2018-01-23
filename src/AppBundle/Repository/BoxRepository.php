<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Repository;

use AppBundle\Entity\Pln;
use Doctrine\ORM\EntityRepository;

/**
 * BoxRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BoxRepository extends EntityRepository {
    
    public function findBoxesQuery(Pln $pln) {
        $qb = $this->createQueryBuilder('b');
        $qb->select('b');
        $qb->andWhere('b.pln = :pln');
        $qb->orderBy('b.id');
        $qb->setParameter('pln', $pln);
        return $qb->getQuery();
    }
    
}
