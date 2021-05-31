<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Entity\Box;
use App\Entity\BoxStatus;
use App\Entity\Pln;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * BoxStatus controller.
 *
 * @Security("is_granted('ROLE_USER')")
 * @Route("/pln/{plnId}/box/{boxId}/status")
 * @ParamConverter("pln", options={"id": "plnId"})
 * @ParamConverter("box", options={"id": "boxId"})
 */
class BoxStatusController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;

    /**
     * Lists all BoxStatus entities.
     *
     * @return array
     *
     * @Route("/", name="box_status_index", methods={"GET"})
     * @Template
     */
    public function indexAction(Request $request, Pln $pln, Box $box) {
        if ($box->getPln() !== $pln) {
            throw new NotFoundHttpException('Unknown box.');
        }
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(BoxStatus::class, 'e')->where('e.box = :box')->orderBy('e.id', 'DESC');
        $qb->setParameter('box', $box);
        $query = $qb->getQuery();

        $boxStatuses = $this->paginator->paginate($query, $request->query->getint('page', 1), 25);

        return [
            'boxStatuses' => $boxStatuses,
            'pln' => $pln,
            'box' => $box,
        ];
    }

    /**
     * Finds and displays a BoxStatus entity.
     *
     * @return array
     *
     * @Route("/{id}", name="box_status_show", methods={"GET"})
     * @Template
     */
    public function showAction(BoxStatus $boxStatus, Pln $pln, Box $box) {
        if ($box->getPln() !== $pln) {
            throw new NotFoundHttpException('Unknown box.');
        }
        if ($boxStatus->getBox() !== $box) {
            throw new NotFoundHttpException('Unknown status.');
        }

        return [
            'boxStatus' => $boxStatus,
            'pln' => $pln,
            'box' => $box,
        ];
    }
}
