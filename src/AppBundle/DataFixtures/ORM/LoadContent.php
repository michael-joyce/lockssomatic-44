<?php

/*
 *  This file is licensed under the MIT License version 3 or
 *  later. See the LICENSE file for details.
 *
 *  Copyright 2018 Michael Joyce <ubermichael@gmail.com>.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Content;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Load some content.
 */
class LoadContent extends Fixture implements DependentFixtureInterface {
    
    /**
     * Load the objects.
     *
     * @param ObjectManager $em
     *   Doctrine object manager.
     */
    public function load(ObjectManager $em) {
        $content1 = new Content();
        $content1->setUrl('http://example.com/path/to/first');
        $content1->setTitle('Content Item 1');
        $content1->setSize(100);
        $content1->setChecksumType('sha1');
        $content1->setChecksumValue('abc123');
        $content1->setDateDeposited(new \DateTime());
        $content1->setDeposit($this->getReference('deposit.1'));
        $content1->setAu($this->getReference('au.1'));
        $content1->setProperty('title', 'Title 1');
        $content1->setProperty('publisher', 'Publisher');
        $em->persist($content1);
        $this->setReference('content.1', $content1);
        
        $content2 = new Content();
        $content2->setUrl('http://example.com/path/to/second');
        $content2->setTitle('Content Item 2');
        $content2->setSize(200);
        $content2->setChecksumType('sha1');
        $content2->setChecksumValue('abc223');
        $content2->setDateDeposited(new \DateTime());
        $content2->setDeposit($this->getReference('deposit.1'));
        $content2->setAu($this->getReference('au.1'));
        $content2->setProperty('title', 'Title 2');
        $content2->setProperty('publisher', 'Publisher');
        $em->persist($content2);
        $this->setReference('content.2', $content2);

        $content3 = new Content();
        $content3->setUrl('http://example.com/path/to/mars');
        $content3->setTitle('Content Item 3');
        $content3->setSize(300);
        $content3->setChecksumType('sha1');
        $content3->setChecksumValue('abc323');
        $content3->setDateDeposited(new \DateTime());
        $content3->setDeposit($this->getReference('deposit.1'));
        $content3->setAu($this->getReference('au.1'));
        $content3->setProperty('title', 'Title 3');
        $content3->setProperty('publisher', 'Publisher');
        $em->persist($content3);
        $this->setReference('content.3', $content3);

        $em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies() {
        return [
            LoadDeposit::class,
            LoadAu::class,
        ];
    }

}
