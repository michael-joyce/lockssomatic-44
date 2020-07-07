<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Entity\Pln;
use App\Form\PlnPropertyType;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;

/**
 * Pln controller.
 *
 * @Security("is_granted('ROLE_USER')")
 * @Route("/pln/{plnId}/property")
 * @ParamConverter("pln", options={"id"="plnId"})
 */
class PlnPropertyController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;
    /**
     * Lists all PLN properties.
     *
     * @return array
     *
     * @Route("/", name="pln_property_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request, Pln $pln) {
        return [
            'pln' => $pln,
        ];
    }

    /**
     * Creates a new Pln property.
     *
     * @return array
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/new", name="pln_property_new")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function newAction(Request $request, Pln $pln) {
        $form = $this->createForm(PlnPropertyType::class, null);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $name = $data['name'];
            $values = $data['values'];
            if (count($values) > 1) {
                $pln->setProperty($name, $values);
            } elseif (1 === count($values)) {
                $pln->setProperty($name, $values[0]);
            } else {
                // count(values) === 0.
                $pln->removeProperty($name);
            }
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'The property has been added to the PLN.');

            return $this->redirectToRoute('pln_property_index', [
                'plnId' => $pln->getId(),
            ]);
        }

        return [
            'pln' => $pln,
            'form' => $form->createView(),
        ];
    }

    /**
     * Displays a form to edit an existing Pln property.
     *
     * @param string $propertyKey
     *
     * @return array
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{propertyKey}/edit", name="pln_property_edit")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function editAction(Request $request, Pln $pln, $propertyKey) {
        $form = $this->createForm(PlnPropertyType::class, null, [
            'name' => $propertyKey,
            'values' => $pln->getProperty($propertyKey),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $name = $data['name'];
            $values = $data['values'];
            if (count($values) > 1) {
                $pln->setProperty($name, $values);
            } elseif (1 === count($values)) {
                $pln->setProperty($name, $values[0]);
            } else {
                // count(values) === 0.
                $pln->removeProperty($name);
            }
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'The property has been updated.');

            return $this->redirectToRoute('pln_property_index', [
                'plnId' => $pln->getId(),
            ]);
        }

        return [
            'pln' => $pln,
            'form' => $form->createView(),
        ];
    }

    /**
     * Deletes a Pln property.
     *
     * @param string $propertyKey
     *
     * @return RedirectResponse
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{propertyKey}/delete", name="pln_property_delete")
     * @Method("GET")
     */
    public function deleteAction(Request $request, Pln $pln, $propertyKey) {
        $pln->removeProperty($propertyKey);
        $this->getDoctrine()->getManager()->flush();
        $this->addFlash('success', 'The property has been removed.');

        return $this->redirectToRoute('pln_property_index', [
            'plnId' => $pln->getId(),
        ]);
    }
}
