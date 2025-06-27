<?php

namespace App\DataFixtures;

use App\Factory\ConseilFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ConseilFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        ConseilFactory::createMany(15);

        $manager->flush();
    }
}
