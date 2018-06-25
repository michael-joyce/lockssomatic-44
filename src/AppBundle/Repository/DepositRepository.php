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
 * Doctrine stub.
 */
class DepositRepository extends EntityRepository {

    public function indexQuery(Pln $pln) {
        $qb = $this->createQueryBuilder('e');
        $qb->innerJoin('e.au', 'a');
        $qb->where('a.pln = :pln');
        $qb->setParameter('pln', $pln);
        $qb->orderBy('e.id', 'desc');
        return $qb->getQuery();
    }

    public function searchQuery($q, Pln $pln = null) {
        $qb = $this->createQueryBuilder('e');
        $qb->addSelect("MATCH(e.uuid, e.url, e.title) AGAINST(:q BOOLEAN) AS HIDDEN score");
        $qb->setParameter('q', $q);
        $qb->andWhere('MATCH(e.uuid, e.url, e.title) AGAINST(:q BOOLEAN) > 0.5');
        $qb->orderBy("score", "desc");
        if ($pln) {
            $qb->innerJoin('e.au', 'a');
            $qb->andWhere('a.pln = :pln');
            $qb->setParameter('pln', $pln);
        }
        return $qb->getQuery();
    }

}
