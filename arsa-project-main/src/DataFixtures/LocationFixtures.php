<?php

namespace App\DataFixtures;

use App\Entity\Location;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LocationFixtures extends Fixture
{
    public function load(ObjectManager $manager) : void
    {
        $locations = [
            [
                'name' => 'South Africa Donation Center',
                'country' => 'South Africa',
                'latitude' => -33.918861,
                'longitude' => 18.423300,
                'donationAmount' => '250000.00',
                'youtubeEmbedUrl' => 'https://www.youtube.com/embed/nXg0Symg_cQ'
            ],
            [
                'name' => 'Kenya Relief Center',
                'country' => 'Kenya',
                'latitude' => -1.292066,
                'longitude' => 36.821945,
                'donationAmount' => '175000.00',
                'youtubeEmbedUrl' => 'https://www.youtube.com/embed/E1xkXZs0cAQ'
            ],
            [
                'name' => 'Senegal Aid Hub',
                'country' => 'Senegal',
                'latitude' => 14.716677,
                'longitude' => -17.467686,
                'donationAmount' => '120000.00',
                'youtubeEmbedUrl' => 'https://www.youtube.com/embed/E1xkXZs0cAQ'
            ],
            [
                'name' => 'Tunisia Main Center',
                'country' => 'Tunisia',
                'latitude' => 36.8065,
                'longitude' => 10.1815,
                'donationAmount' => '300000.00',
                'youtubeEmbedUrl' => 'https://www.youtube.com/embed/nXg0Symg_cQ'
            ],
            [
                'name' => 'Nigeria Support Center',
                'country' => 'Nigeria',
                'latitude' => 6.524379,
                'longitude' => 3.379206,
                'donationAmount' => '280000.00',
                'youtubeEmbedUrl' => 'https://www.youtube.com/embed/E1xkXZs0cAQ'
            ],
            [
                'name' => 'Ethiopia Aid Station',
                'country' => 'Ethiopia',
                'latitude' => 9.145000,
                'longitude' => 40.489673,
                'donationAmount' => '190000.00',
                'youtubeEmbedUrl' => 'https://www.youtube.com/embed/nXg0Symg_cQ'
            ],
            [
                'name' => 'Morocco Distribution Hub',
                'country' => 'Morocco',
                'latitude' => 31.791702,
                'longitude' => -7.092620,
                'donationAmount' => '210000.00',
                'youtubeEmbedUrl' => 'https://www.youtube.com/embed/E1xkXZs0cAQ'
            ],
            [
                'name' => 'Ghana Relief Center',
                'country' => 'Ghana',
                'latitude' => 7.946527,
                'longitude' => -1.023194,
                'donationAmount' => '165000.00',
                'youtubeEmbedUrl' => 'https://www.youtube.com/embed/nXg0Symg_cQ'
            ],
            [
                'name' => 'Rwanda Support Hub',
                'country' => 'Rwanda',
                'latitude' => -1.940278,
                'longitude' => 29.873888,
                'donationAmount' => '145000.00',
                'youtubeEmbedUrl' => 'https://www.youtube.com/embed/E1xkXZs0cAQ'
            ],
            [
                'name' => 'Uganda Aid Center',
                'country' => 'Uganda',
                'latitude' => 1.373333,
                'longitude' => 32.290275,
                'donationAmount' => '155000.00',
                'youtubeEmbedUrl' => 'https://www.youtube.com/embed/nXg0Symg_cQ'
            ]
        ];

        foreach ($locations as $locationData) {
            $location = new Location();
            $location->setName($locationData['name']);
            $location->setCountry($locationData['country']);
            $location->setLatitude((string)$locationData['latitude']);
            $location->setLongitude((string)$locationData['longitude']);
            $location->setDonationAmount($locationData['donationAmount']);
            $location->setYoutubeEmbedUrl($locationData['youtubeEmbedUrl']);
            
            $manager->persist($location);
        }

        $manager->flush();
    }
}