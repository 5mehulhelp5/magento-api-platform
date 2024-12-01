<?php

namespace App\DataFixtures;

use App\Entity\Main\CustomerConsolidated;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CustomerFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 100; $i++) {
            $customer = new CustomerConsolidated();
            $customer->setEmail('customer' . $i . '@example.com');
            $manager->persist($customer);
        }

        $manager->flush();
    }
}
