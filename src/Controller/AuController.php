<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Entity\Au;
use App\Entity\Pln;
use App\Services\AuManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Au controller.
 *
 * @Security("is_granted('ROLE_USER')")
 * @Route("/pln/{plnId}/au")
 * @ParamConverter("pln", options={"id"="plnId"})
 */
class AuController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;

    /**
     * Lists all Au entities.
     *
     * @return array
     *
     * @Route("/", name="au_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request, Pln $pln, AuManager $manager) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(Au::class, 'e')->where('e.pln = :pln')->orderBy('e.id', 'ASC');
        $qb->setParameter('pln', $pln);
        $query = $qb->getQuery();

        $aus = $this->paginator->paginate($query, $request->query->getint('page', 1), 25);

        return [
            'aus' => $aus,
            'pln' => $pln,
            'manager' => $manager,
        ];
    }

    /**
     * Finds and displays a Au entity.
     *
     * @return array
     *
     * @Route("/{id}", name="au_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(Au $au, Pln $pln, AuManager $manager) {
        if ($au->getPln() !== $pln) {
            throw new NotFoundHttpException("PLN {$pln->getName()} does not contain that AU.");
        }

        return [
            'au' => $au,
            'pln' => $pln,
            'manager' => $manager,
        ];
    }

    /**
     * Finds and displays a Au entity.
     *
     * @return array
     *
     * @Route("/{id}/deposits", name="au_deposits")
     * @Method("GET")
     * @Template()
     */
    public function depositsAction(Request $request, Pln $pln, Au $au, EntityManagerInterface $em) {
        if ($au->getPln() !== $pln) {
            throw new NotFoundHttpException("PLN {$pln->getName()} does not contain that AU.");
        }
        $repo = $em->getRepository(Au::class);
        $query = $repo->queryDeposits($au);

        $deposits = $this->paginator->paginate($query, $request->query->getint('page', 1), 25);

        return [
            'au' => $au,
            'pln' => $pln,
            'deposits' => $deposits,
        ];
    }
}
