<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Association;
use App\Entity\Location;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }
    #[Route('/admin/delete/{idUser}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, int $idUser): Response
    {
        // Fetch the user by ID from the UserRepository
        $user = $userRepository->find($idUser);

        // If the user is not found, throw an exception or handle the error
        if (!$user) {
            throw $this->createNotFoundException('No user found for id ' . $idUser);
        }

        // Check if the CSRF token is valid
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            // Remove the user and flush the changes to the database
            $entityManager->remove($user);
            $entityManager->flush();

            // Optionally add a flash message indicating success
            $this->addFlash('success', 'User deleted successfully!');
        }

        // Redirect back to the user listing page
        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/admin/user/edit/{idUser}', name: 'admin_edit_user')]
    public function edit(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, int $idUser): Response
    {
        // Find the user by ID
        $user = $userRepository->find($idUser);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
    
        // If the form is submitted via POST
        if ($request->isMethod('POST')) {
            $user->setNom($request->request->get('nom'));
            $user->setEmail($request->request->get('email'));
            $user->setTel($request->request->get('tel'));
            $user->setAdress($request->request->get('adress'));
            $user->setRole($request->request->get('role'));
    
            // Save the changes
            $entityManager->flush();
    
            // Redirect to the user list after updating
            return $this->redirectToRoute('admin_user_list');
        }
    
        // Render the edit form
        return $this->render('admin/edit.html.twig', [
            'user' => $user,
        ]);
    }
    #[Route('/admin/tables', name: 'admin_tables')]
    public function tables(Request $request, EntityManagerInterface $entityManager): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 5;
        $maxPages = 5; 
    
        $page = $request->query->getInt('page', 1);
        $limit = 5;
        $maxPages = 5;
    
        
        $eventRepository = $entityManager->getRepository(Event::class);
        $totalEvents = $eventRepository->count([]);
        $totalPages = ceil($totalEvents / $limit);
        
        $startPage = max(1, min($page - floor($maxPages / 2), $totalPages - $maxPages + 1));
        $endPage = min($startPage + $maxPages - 1, $totalPages);
        
        $offset = ($page - 1) * $limit;
        $events = $eventRepository->findBy(
            [],
            ['id' => 'DESC'],
            $limit,
            $offset
        );
        
        
        $associations = $entityManager->getRepository(Association::class)->findAll();
        $locations = $entityManager->getRepository(Location::class)->findAll();
        
        return $this->render('ArsBack/tables.html.twig', [
            'events' => $events,
            'associations' => $associations,
            'locations' => $locations,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'startPage' => $startPage,
            'endPage' => $endPage
        ]);
    }

        #[Route('/admin/event/{id}', name: 'admin_event_get', methods: ['GET'])]
        public function getEvent(Event $event): JsonResponse
        {
            return new JsonResponse([
                'id' => $event->getId(),
                'name' => $event->getName(),
                'associationId' => $event->getAssociation()->getId(),
                'eventDate' => $event->getEventDate()->format('Y-m-d'),
                'price' => $event->getPrice(),
                'location' => [
                    'id' => $event->getLocation()->getId(),
                    'name' => $event->getLocation()->getName()
                ],
                'type' => $event->getType(),
                'imageFilename' => $event->getImageFilename()
            ]);
        }
        

        
        #[Route('/admin/events/search', name: 'admin_events_search', methods: ['GET'])]
        public function searchEvents(Request $request, EntityManagerInterface $entityManager): JsonResponse
        {
            try {
                $searchTerm = $request->query->get('term');
                
                error_log('Search term received: ' . $searchTerm);
        
                $qb = $entityManager->getRepository(Event::class)->createQueryBuilder('e')
                    ->leftJoin('e.association', 'a')
                    ->leftJoin('e.location', 'l')
                    ->where('e.name LIKE :term')
                    ->orWhere('l.name LIKE :term')
                    ->orWhere('e.eventDate LIKE :date_term')
                    ->setParameter('term', '%' . $searchTerm . '%')
                    ->setParameter('date_term', '%' . $searchTerm . '%')
                    ->orderBy('e.id', 'DESC');
        
                $events = $qb->getQuery()->getResult();
                error_log('Number of results: ' . count($events));
        
                $results = array_map(function($event) {
                    return [
                        'id' => $event->getId(),
                        'name' => $event->getName(),
                        'association' => $event->getAssociation()->getName(),
                        'eventDate' => $event->getEventDate()->format('Y-m-d'),
                        'price' => $event->getPrice(),
                        'location' => [
                            'id' => $event->getLocation()->getId(),
                            'name' => $event->getLocation()->getName()
                        ],
                        'type' => $event->getType(),
                        'imageFilename' => $event->getImageFilename()
                    ];
                }, $events);
        
                return new JsonResponse([
                    'success' => true,
                    'events' => $results
                ]);
            } catch (\Exception $e) {
                error_log('Search error: ' . $e->getMessage());
                return new JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 400);
            }
        }

            #[Route('/admin/association/{id}', name: 'admin_association_get', methods: ['GET'])]
        public function getAssociation(Association $association): JsonResponse
        {
            return new JsonResponse([
                'id' => $association->getId(),
                'name' => $association->getName(),
                'description' => $association->getDescription(),
                'logo' => $association->getLogo()
            ]);
        }

        #[Route('/admin/location/{id}', name: 'admin_location_get', methods: ['GET'])]
        public function getLocation(Location $location): JsonResponse
        {
            return new JsonResponse([
                'id' => $location->getId(),
                'name' => $location->getName(),
                'country' => $location->getCountry(),
                'latitude' => $location->getLatitude(),
                'longitude' => $location->getLongitude(),
                'donationAmount' => $location->getDonationAmount(),
                'youtubeEmbedUrl' => $location->getYoutubeEmbedUrl()
            ]);
        }
        #[Route('/admin/event/create', name: 'admin_event_create', methods: ['POST'])]
        public function createEvent(Request $request, EntityManagerInterface $entityManager): JsonResponse
        {
            try {
                
                $name = $request->request->get('name');
                $associationId = $request->request->get('associationId');
                $date = $request->request->get('date');
                $price = $request->request->get('price');
                $locationId = $request->request->get('location'); 
                $type = $request->request->get('type');
        
                
                if (!$name || !$associationId || !$date || !$price || !$locationId || !$type) {
                    throw new \Exception('All fields are required');
                }
        
                $association = $entityManager->getRepository(Association::class)->find($associationId);
                if (!$association) {
                    throw new \Exception('Association not found');
                }
        
                $location = $entityManager->getRepository(Location::class)->find($locationId);
                if (!$location) {
                    throw new \Exception('Location not found');
                }
        
                $uploadedFile = $request->files->get('image');
                $imageFilename = null;
                
                if ($uploadedFile) {
                    $newFilename = uniqid().'.'.$uploadedFile->guessExtension();
                    
                    $uploadDir = $this->getParameter('event_images_directory');
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    try {
                        $uploadedFile->move(
                            $uploadDir,
                            $newFilename
                        );
                        $imageFilename = $newFilename;
                    } catch (\Exception $e) {
                        throw new \Exception('Failed to upload image: ' . $e->getMessage());
                    }
                }
        
                $event = new Event();
                $event->setName($name);
                $event->setAssociation($association);
                $event->setEventDate(new \DateTime($date));
                $event->setPrice((float)$price);
                $event->setLocation($location);
                $event->setType($type);
                $event->setImageFilename($imageFilename);
        
                $entityManager->persist($event);
                $entityManager->flush();
        
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Event created successfully'
                ]);
                
            } catch (\Exception $e) {
                return new JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 400);
            }
        }
        #[Route('/admin/event/{id}', name: 'admin_event_delete', methods: ['DELETE'])]
        public function deleteEvent(Event $event, EntityManagerInterface $entityManager): JsonResponse
        {
            try {
                if ($event->getImageFilename()) {
                    $imagePath = $this->getParameter('event_images_directory') . '/' . $event->getImageFilename();
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
                
                $entityManager->remove($event);
                $entityManager->flush();
                
                return new JsonResponse(['success' => true]);
            } catch (\Exception $e) {
                return new JsonResponse(['success' => false, 'error' => $e->getMessage()]);
            }
        }

       
        


        

            #[Route('/admin/event/{id}/update', name: 'admin_event_update', methods: ['POST'])]
            public function updateEvent(Request $request, Event $event, EntityManagerInterface $entityManager): JsonResponse
            {
                try {
                    $event->setName($request->request->get('name'));
                    
                    $associationId = $request->request->get('associationId');
                    $association = $entityManager->getRepository(Association::class)->find($associationId);
                    if (!$association) {
                        throw new \Exception('Association not found');
                    }
                    $event->setAssociation($association);
                    
                    $event->setEventDate(new \DateTime($request->request->get('date')));
                    $event->setPrice((float)$request->request->get('price'));
                    
                    $locationId = $request->request->get('location');
                    $location = $entityManager->getRepository(Location::class)->find($locationId);
                    if (!$location) {
                        throw new \Exception('Location not found');
                    }
                    $event->setLocation($location);
                    
                    $event->setType($request->request->get('type'));
                    
                    $uploadedFile = $request->files->get('image');
                    if ($uploadedFile) {
                        if ($event->getImageFilename()) {
                            $oldImagePath = $this->getParameter('event_images_directory') . '/' . $event->getImageFilename();
                            if (file_exists($oldImagePath)) {
                                unlink($oldImagePath);
                            }
                        }
                        
                        $newFilename = uniqid().'.'.$uploadedFile->guessExtension();
                        $uploadedFile->move(
                            $this->getParameter('event_images_directory'),
                            $newFilename
                        );
                        $event->setImageFilename($newFilename);
                    }

                    $entityManager->flush();

                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Event updated successfully'
                    ]);
                } catch (\Exception $e) {
                    return new JsonResponse([
                        'success' => false,
                        'error' => $e->getMessage()
                    ], 400);
                }
            }


        #[Route('/admin/association/create', name: 'admin_association_create', methods: ['POST'])]
        public function createAssociation(Request $request, EntityManagerInterface $entityManager): JsonResponse
        {
            try {
                $name = $request->request->get('name');
                $description = $request->request->get('description');
                
                $uploadedFile = $request->files->get('logo');
                $logoFilename = null;
                
                if ($uploadedFile) {
                    $newFilename = uniqid().'.'.$uploadedFile->guessExtension();
                    try {
                        $uploadedFile->move(
                            $this->getParameter('association_images_directory'),
                            $newFilename
                        );
                        $logoFilename = $newFilename;
                    } catch (\Exception $e) {
                        return new JsonResponse([
                            'success' => false,
                            'error' => 'Failed to upload logo: ' . $e->getMessage()
                        ], 400);
                    }
                }

                $association = new Association();
                $association->setName($name);
                $association->setDescription($description);
                $association->setLogo($logoFilename);

                $entityManager->persist($association);
                $entityManager->flush();

                return new JsonResponse(['success' => true]);
            } catch (\Exception $e) {
                return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 400);
            }
        }

        #[Route('/admin/location/create', name: 'admin_location_create', methods: ['POST'])]
        public function createLocation(Request $request, EntityManagerInterface $entityManager): JsonResponse
        {
            try {
                $name = $request->request->get('name');
                $country = $request->request->get('country');
                $latitude = $request->request->get('latitude');
                $longitude = $request->request->get('longitude');
                $donationAmount = $request->request->get('donationAmount');
                $youtubeUrl = $request->request->get('youtubeUrl');

                $location = new Location();
                $location->setName($name);
                $location->setCountry($country);
                $location->setLatitude($latitude);
                $location->setLongitude($longitude);
                $location->setDonationAmount($donationAmount);
                $location->setYoutubeEmbedUrl($youtubeUrl);

                $entityManager->persist($location);
                $entityManager->flush();

                return new JsonResponse(['success' => true]);
            } catch (\Exception $e) {
                return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 400);
            }
        }

        #[Route('/admin/association/{id}', name: 'admin_association_delete', methods: ['DELETE'])]
        public function deleteAssociation(Association $association, EntityManagerInterface $entityManager): JsonResponse
        {
            try {
                if ($association->getLogo()) {
                    $logoPath = $this->getParameter('association_images_directory') . '/' . $association->getLogo();
                    if (file_exists($logoPath)) {
                        unlink($logoPath);
                    }
                }
                
                $entityManager->remove($association);
                $entityManager->flush();
                
                return new JsonResponse(['success' => true]);
            } catch (\Exception $e) {
                return new JsonResponse(['success' => false, 'error' => $e->getMessage()]);
            }
        }

        #[Route('/admin/locations/search', name: 'admin_locations_search', methods: ['GET'])]
        public function searchLocations(Request $request, EntityManagerInterface $entityManager): JsonResponse
        {
            try {
                $searchTerm = $request->query->get('term');
                
                $qb = $entityManager->getRepository(Location::class)->createQueryBuilder('l')
                    ->where('l.name LIKE :term')
                    ->orWhere('l.country LIKE :term')
                    ->setParameter('term', '%' . $searchTerm . '%')
                    ->orderBy('l.id', 'DESC');
        
                $locations = $qb->getQuery()->getResult();
        
                $results = array_map(function($location) {
                    return [
                        'id' => $location->getId(),
                        'name' => $location->getName(),
                        'country' => $location->getCountry(),
                        'latitude' => $location->getLatitude(),
                        'longitude' => $location->getLongitude(),
                        'donationAmount' => $location->getDonationAmount(),
                        'youtubeEmbedUrl' => $location->getYoutubeEmbedUrl()
                    ];
                }, $locations);
        
                return new JsonResponse([
                    'success' => true,
                    'locations' => $results
                ]);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 400);
            }
        }

            #[Route('/admin/location/{id}/update', name: 'admin_location_update', methods: ['POST'])]
            public function updateLocation(Request $request, Location $location, EntityManagerInterface $entityManager): JsonResponse
            {
                try {
                    $location->setName($request->request->get('name'));
                    $location->setCountry($request->request->get('country'));
                    $location->setLatitude($request->request->get('latitude'));
                    $location->setLongitude($request->request->get('longitude'));
                    $location->setDonationAmount($request->request->get('donationAmount'));
                    $location->setYoutubeEmbedUrl($request->request->get('youtubeUrl'));

                    $entityManager->flush();

                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Location updated successfully'
                    ]);
                } catch (\Exception $e) {
                    return new JsonResponse([
                        'success' => false,
                        'error' => $e->getMessage()
                    ], 400);
                }
            }

            #[Route('/admin/location/{id}', name: 'admin_location_delete', methods: ['DELETE'])]
            public function deleteLocation(Location $location, EntityManagerInterface $entityManager): JsonResponse
            {
                try {
                    $entityManager->remove($location);
                    $entityManager->flush();
                    
                    return new JsonResponse(['success' => true]);
                } catch (\Exception $e) {
                    return new JsonResponse([
                        'success' => false,
                        'error' => $e->getMessage()
                    ], 400);
                }
            }
            #[Route('/admin/association/{id}/update', name: 'admin_association_update', methods: ['POST'])]
            public function updateAssociation(Request $request, Association $association, EntityManagerInterface $entityManager): JsonResponse
            {
                try {
                    $association->setName($request->request->get('name'));
                    $association->setDescription($request->request->get('description'));
                    
                    $uploadedFile = $request->files->get('logo');
                    if ($uploadedFile) {
                        if ($association->getLogo()) {
                            $oldLogoPath = $this->getParameter('association_images_directory') . '/' . $association->getLogo();
                            if (file_exists($oldLogoPath)) {
                                unlink($oldLogoPath);
                            }
                        }
                        
                        $newFilename = uniqid().'.'.$uploadedFile->guessExtension();
                        $uploadedFile->move(
                            $this->getParameter('association_images_directory'),
                            $newFilename
                        );
                        $association->setLogo($newFilename);
                    }

                    $entityManager->flush();

                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Association updated successfully'
                    ]);
                } catch (\Exception $e) {
                    return new JsonResponse([
                        'success' => false,
                        'error' => $e->getMessage()
                    ], 400);
                }
            }
                #[Route('/admin/associations/search', name: 'admin_associations_search', methods: ['GET'])]
                public function searchAssociations(Request $request, EntityManagerInterface $entityManager): JsonResponse
                {
                    try {
                        $searchTerm = $request->query->get('term');
                        
                        $qb = $entityManager->getRepository(Association::class)->createQueryBuilder('a')
                            ->where('a.name LIKE :term')
                            ->setParameter('term', '%' . $searchTerm . '%')
                            ->orderBy('a.id', 'DESC');

                        $associations = $qb->getQuery()->getResult();

                        $results = array_map(function($association) {
                            return [
                                'id' => $association->getId(),
                                'name' => $association->getName(),
                                'description' => $association->getDescription(),
                                'logo' => $association->getLogo()
                            ];
                        }, $associations);

                        return new JsonResponse([
                            'success' => true,
                            'associations' => $results
                        ]);
                    } catch (\Exception $e) {
                        return new JsonResponse([
                            'success' => false,
                            'error' => $e->getMessage()
                        ], 400);
                    }
                }



        



        
        
}
