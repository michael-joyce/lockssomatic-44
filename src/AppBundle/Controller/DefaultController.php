<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default controller for LOCKSSOMatic. Totally open to the public.
 */
class DefaultController extends Controller {

    /**
     * LOCKSSOMatic home page.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request, EntityManagerInterface $em) {
        $user = $this->getUser();

        if (!$user || !$user->hasRole('ROLE_USER')) {
            return $this->render('default/index_anon.html.twig');
        }

        return $this->render('default/index_user.html.twig', array(
        ));
    }

}
