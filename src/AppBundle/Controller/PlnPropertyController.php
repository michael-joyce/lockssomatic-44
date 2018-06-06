<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Pln;
use AppBundle\Form\PlnPropertyType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Pln controller.
 *
 * @Security("has_role('ROLE_USER')")
 * @Route("/pln/{plnId}/property")
 * @ParamConverter("pln", options={"id"="plnId"})
 */
class PlnPropertyController extends Controller {

    /**
     * Lists all PLN properties.
     *
     * @param Request $request
     * @param Pln $pln
     *
     * @return array
     *
     * @Route("/", name="pln_property_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request, Pln $pln) {
        return array(
            'pln' => $pln,
        );
    }

    /**
     * Creates a new Pln property.
     *
     * @param Request $request
     * @param Pln $pln
     *
     * @return array
     *
     * @Security("has_role('ROLE_ADMIN')")
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
            } elseif (count($values) === 1) {
                $pln->setProperty($name, $values[0]);
            } else {
                // count(values) === 0.
                $pln->removeProperty($name);
            }
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'The property has been added to the PLN.');
            return $this->redirectToRoute('pln_property_index', array(
                        'plnId' => $pln->getId(),
            ));
        }
        return array(
            'pln' => $pln,
            'form' => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Pln property.
     *
     * @param Request $request
     * @param Pln $pln
     * @param string $propertyKey
     *
     * @return array
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{propertyKey}/edit", name="pln_property_edit")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function editAction(Request $request, Pln $pln, $propertyKey) {
        $form = $this->createForm(PlnPropertyType::class, null, array(
            'name' => $propertyKey,
            'values' => $pln->getProperty($propertyKey),
        ));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $name = $data['name'];
            $values = $data['values'];
            if (count($values) > 1) {
                $pln->setProperty($name, $values);
            } elseif (count($values) === 1) {
                $pln->setProperty($name, $values[0]);
            } else {
                // count(values) === 0.
                $pln->removeProperty($name);
            }
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'The property has been updated.');
            return $this->redirectToRoute('pln_property_index', array(
                        'plnId' => $pln->getId(),
            ));
        }
        return array(
            'pln' => $pln,
            'form' => $form->createView(),
        );
    }

    /**
     * Deletes a Pln property.
     *
     * @param Request $request
     * @param Pln $pln
     * @param string $propertyKey
     *
     * @return RedirectResponse
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/{propertyKey}/delete", name="pln_property_delete")
     * @Method("GET")
     */
    public function deleteAction(Request $request, Pln $pln, $propertyKey) {
        $pln->removeProperty($propertyKey);
        $this->getDoctrine()->getManager()->flush();
        $this->addFlash('success', 'The property has been removed.');
        return $this->redirectToRoute('pln_property_index', array(
                    'plnId' => $pln->getId(),
        ));
    }

}
