<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\DepositStatus;
use AppBundle\Entity\Pln;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * DepositStatus controller.
 *
 * @Security("has_role('ROLE_USER')")
 * @Route("/pln/{plnId}/deposit/{depositId}/status")
 * @ParamConverter("pln", options={"id"="plnId"})
 * @ParamConverter("deposit", options={"id"="depositId"})
 */
class DepositStatusController extends Controller {
    /**
     * Lists all DepositStatus entities.
     *
     * @return array
     *
     * @Route("/", name="deposit_status_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request, Pln $pln, Deposit $deposit) {
        if ($deposit->getAu()->getPln() !== $pln) {
            throw new NotFoundHttpException('No such deposit.');
        }
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(DepositStatus::class, 'e')->where('e.deposit = :deposit')->orderBy('e.id', 'ASC');
        $qb->setParameter('deposit', $deposit);
        $query = $qb->getQuery();
        $paginator = $this->get('knp_paginator');
        $depositStatuses = $paginator->paginate($query, $request->query->getint('page', 1), 25);

        return [
            'depositStatuses' => $depositStatuses,
            'deposit' => $deposit,
            'pln' => $pln,
        ];
    }

    /**
     * Finds and displays a DepositStatus entity.
     *
     * @return array
     *
     * @Route("/{id}", name="deposit_status_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(DepositStatus $depositStatus, Pln $pln, Deposit $deposit) {
        if ($deposit->getAu()->getPln() !== $pln) {
            throw new NotFoundHttpException('No such deposit.');
        }
        if ($depositStatus->getDeposit() !== $deposit) {
            throw new NotFoundHttpException('No such deposit status.');
        }

        return [
            'depositStatus' => $depositStatus,
            'deposit' => $deposit,
            'pln' => $pln,
        ];
    }
}
