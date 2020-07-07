<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Entity\Deposit;
use App\Entity\Pln;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;

/**
 * Deposit controller.
 *
 * @Security("is_granted('ROLE_USER')")
 * @Route("/pln/{plnId}/deposit")
 * @ParamConverter("pln", options={"id"="plnId"})
 */
class DepositController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;
    /**
     * Lists all Deposit entities.
     *
     * @return array
     *
     * @Route("/", name="deposit_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request, Pln $pln) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(Deposit::class);
        $query = $repo->indexQuery($pln);

        $deposits = $this->paginator->paginate($query, $request->query->getint('page', 1), 25);

        return [
            'deposits' => $deposits,
            'pln' => $pln,
        ];
    }

    /**
     * Search for Deposit entities.
     *
     * @return array
     *
     * @Route("/search", name="deposit_search")
     * @Method("GET")
     * @Template()
     */
    public function searchAction(Request $request, Pln $pln) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('App:Deposit');
        $q = $request->query->get('q');
        if ($q) {
            $query = $repo->searchQuery($q, $pln);

            $deposits = $this->paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $deposits = [];
        }

        return [
            'deposits' => $deposits,
            'q' => $q,
            'pln' => $pln,
        ];
    }

    /**
     * Finds and displays a Deposit entity.
     *
     * @return array
     *
     * @Route("/{id}", name="deposit_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(Deposit $deposit, Pln $pln) {
        if ($deposit->getAu()->getPln() !== $pln) {
            throw new NotFoundHttpException('No such deposit.');
        }

        return [
            'deposit' => $deposit,
            'pln' => $pln,
        ];
    }
}
