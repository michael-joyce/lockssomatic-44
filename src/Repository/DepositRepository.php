<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Repository;

use App\Entity\Deposit;
use App\Entity\Pln;
use App\Entity\Plugin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function searchQuery($q, Pln $pln = null) {
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
}
