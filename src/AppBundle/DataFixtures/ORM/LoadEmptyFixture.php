<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Description of LoadBox
 *
 * @author Michael Joyce <ubermichael@gmail.com>
 */
class LoadEmptyFixture extends Fixture {
    
    public function load(ObjectManager $em) {
    }

}
