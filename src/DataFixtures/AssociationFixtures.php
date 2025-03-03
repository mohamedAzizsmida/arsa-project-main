<?php

namespace App\DataFixtures;

use App\Entity\Association;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AssociationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $associations = [
            // International Associations
            ['name' => 'UNICEF', 'description' => 'United Nations Children\'s Fund'],
            ['name' => 'Red Cross', 'description' => 'International humanitarian organization'],
            ['name' => 'Doctors Without Borders', 'description' => 'Medical humanitarian organization'],
            ['name' => 'WHO', 'description' => 'World Health Organization'],
            ['name' => 'Save the Children', 'description' => 'Children\'s rights organization'],
            ['name' => 'Islamic Relief', 'description' => 'International humanitarian aid organization'],
            ['name' => 'Human Rights Watch', 'description' => 'International human rights organization'],
            
            // Tunisian Associations
            ['name' => 'Association Tunisienne des Femmes Démocrates', 'description' => 'Women\'s rights organization in Tunisia'],
            ['name' => 'SOS Villages d\'Enfants Tunisie', 'description' => 'Tunisian children\'s welfare organization'],
            ['name' => 'Croissant-Rouge Tunisien', 'description' => 'Tunisian Red Crescent Society'],
            ['name' => 'Association Tunisienne de Lutte contre le Cancer', 'description' => 'Tunisian cancer fighting organization'],
            ['name' => 'Association Tunisienne d\'Action Sociale', 'description' => 'Tunisian social action organization'],
            ['name' => 'La Voix de l\'Enfant Tunisien', 'description' => 'Child welfare organization in Tunisia'],
            ['name' => 'Association Amal', 'description' => 'Hope association for humanitarian aid in Tunisia'],
            ['name' => 'Tunisian Association for Management and Social Stability', 'description' => 'Social development in Tunisia'],
            ['name' => 'Association Tunisienne de Soutien à la Famille', 'description' => 'Family support organization'],
            ['name' => 'Association Tunisienne des Droits de l\'Enfant', 'description' => 'Children\'s rights in Tunisia']
        ];

        foreach ($associations as $assocData) {
            $association = new Association();
            $association->setName($assocData['name']);
            $association->setDescription($assocData['description']);
            $manager->persist($association);
        }

        $manager->flush();
    }
}