<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Entity\Deposit;
use App\Entity\DepositStatus;
use App\Entity\Pln;
use Nines\UtilBundle\Controller\PaginatorTrait;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;

/**
 * DepositStatus controller.
 *
 * @Security("is_granted('ROLE_USER')")
 * @Route("/pln/{plnId}/deposit/{depositId}/status")
 * @ParamConverter("pln", options={"id"="plnId"})
 * @ParamConverter("deposit", options={"id"="depositId"})
 */
class DepositStatusController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;
    /**
     * Lists all DepositStatus entities.
     *
     * @return array
     *
     * @Route("/", name="deposit_status_index", methods={"GET"})
     *
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

        $depositStatuses = $this->paginator->paginate($query, $request->query->getint('page', 1), 25);

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
     * @Route("/{id}", name="deposit_status_show", methods={"GET"})
     *
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
