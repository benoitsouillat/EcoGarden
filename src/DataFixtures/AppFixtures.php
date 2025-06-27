<?php

namespace App\DataFixtures;

use App\Factory\AdministratorFactory;
use App\Factory\ConseilFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        UserFactory::createMany(10);
        AdministratorFactory::createOne();
        ConseilFactory::createMany(15);
        $manager->flush();
    }
}
