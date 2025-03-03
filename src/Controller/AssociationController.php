<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Association;
use App\Entity\Location;
use App\Entity\Donation;
use App\Entity\Post;
use App\Service\EmailService;
use App\Form\PostType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class AssociationController extends AbstractController
{
    #[Route('/association', name: 'association_dashboard')]
    public function index(EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        $associationId = $session->get('association_id');
        $association = $entityManager->getRepository(Association::class)->find($associationId);
        $associations = $entityManager->getRepository(Association::class)->findAll();

        return $this->render('association/index.html.twig', [
            'controller_name' => 'AssociationController',
            'association' => $association,
            'associations' => $associations
        ]);
    }

    #[Route('/association/login', name: 'association_login')]
    public function login(Request $request, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        $associationId = $request->query->get('associationId');
        $association = $entityManager->getRepository(Association::class)->find($associationId);

        if ($association) {
            $session->set('association_id', $association->getId());
        }

        return $this->redirectToRoute('association_dashboard');
    }
    
    #[Route('/association/tables', name: 'association_tables')]
    public function tables(Request $request, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        $associationId = $session->get('association_id');
        $page = $request->query->getInt('page', 1);
        $limit = 5;
        $maxPages = 5;

        $eventRepository = $entityManager->getRepository(Event::class);
        $qb = $eventRepository->createQueryBuilder('e')
            ->where('e.association = :associationId')
            ->setParameter('associationId', $associationId)
            ->orderBy('e.id', 'DESC');

        $totalEvents = count($qb->getQuery()->getResult());
        $totalPages = ceil($totalEvents / $limit);
        
        $startPage = max(1, min($page - floor($maxPages / 2), $totalPages - $maxPages + 1));
        $endPage = min($startPage + $maxPages - 1, $totalPages);
        
        $offset = ($page - 1) * $limit;
        
        $events = $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $locations = $entityManager->getRepository(Location::class)->findAll();
        
        return $this->render('association/tables.html.twig', [
            'events' => $events,
            'association' => $entityManager->getRepository(Association::class)->find($associationId),
            'locations' => $locations,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'startPage' => $startPage,
            'endPage' => $endPage
        ]);
    }

    #[Route('/association/events/search', name: 'association_events_search', methods: ['GET'])]
    public function searchEvents(Request $request, EntityManagerInterface $entityManager, SessionInterface $session): JsonResponse
    {
        try {
            $associationId = $session->get('association_id');
            $searchTerm = $request->query->get('term');
            
            $qb = $entityManager->getRepository(Event::class)->createQueryBuilder('e')
                ->leftJoin('e.association', 'a')
                ->leftJoin('e.location', 'l')
                ->where('e.association = :associationId')
                ->andWhere('(e.name LIKE :term OR l.name LIKE :term OR e.eventDate LIKE :date_term)')
                ->setParameter('associationId', $associationId)
                ->setParameter('term', '%' . $searchTerm . '%')
                ->setParameter('date_term', '%' . $searchTerm . '%')
                ->orderBy('e.id', 'DESC');

            $events = $qb->getQuery()->getResult();

            $results = array_map(function($event) use ($entityManager, $associationId) {
                $association = $entityManager->getRepository(Association::class)->find($associationId);
                return [
                    'id' => $event->getId(),
                    'name' => $event->getName(),
                    'association' => $association->getName(),
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
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/association/event/{id}', name: 'association_event_delete', methods: ['DELETE'])]
    public function deleteEvent(Event $event, EntityManagerInterface $entityManager, SessionInterface $session): JsonResponse
    {
        try {
            $associationId = $session->get('association_id');
            if ($event->getAssociation()->getId() !== $associationId) {
                throw new \Exception('Unauthorized access to this event');
            }

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
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/association/event/{id}', name: 'association_event_get', methods: ['GET'])]
    public function getEvent(Event $event, SessionInterface $session): JsonResponse
    {
        $associationId = $session->get('association_id');
        if ($event->getAssociation()->getId() !== $associationId) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Unauthorized access to this event'
            ], 403);
        }

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

    #[Route('/association/event/{id}/update', name: 'association_event_update', methods: ['POST'])]
    public function updateEvent(Request $request, Event $event, EntityManagerInterface $entityManager, SessionInterface $session): JsonResponse
    {
        try {
            $associationId = $session->get('association_id');
            if ($event->getAssociation()->getId() !== $associationId) {
                throw new \Exception('Unauthorized access to this event');
            }

            $event->setName($request->request->get('name'));
            $event->setEventDate(new \DateTime($request->request->get('eventDate')));
            $event->setPrice($request->request->get('price'));
            
            $location = $entityManager->getRepository(Location::class)->find($request->request->get('locationId'));
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

    
    #[Route('/association/event/create', name: 'association_event_create', methods: ['POST'])]
    public function createEvent(Request $request, EntityManagerInterface $entityManager, SessionInterface $session): JsonResponse
    {
        try {
            $associationId = $session->get('association_id');
            $event = new Event();
            $event->setName($request->request->get('name'));
            $event->setEventDate(new \DateTime($request->request->get('eventDate')));
            $event->setPrice($request->request->get('price'));
            
            // Get location
            $location = $entityManager->getRepository(Location::class)->find($request->request->get('locationId'));
            if (!$location) {
                throw new \Exception('Location not found');
            }
            $event->setLocation($location);
            
            // Set the association
            $association = $entityManager->getRepository(Association::class)->find($associationId);
            if (!$association) {
                throw new \Exception('Association not found');
            }
            $event->setAssociation($association);
            
            $event->setType($request->request->get('type'));

            // Handle image upload
            $uploadedFile = $request->files->get('image');
            if ($uploadedFile) {
                $newFilename = uniqid().'.'.$uploadedFile->guessExtension();
                $uploadedFile->move(
                    $this->getParameter('event_images_directory'),
                    $newFilename
                );
                $event->setImageFilename($newFilename);
            }

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
    
    #[Route('/association/event/{id}/donors', name: 'association_event_donors', methods: ['GET'])]
    public function getEventDonors(int $id, EntityManagerInterface $entityManager, SessionInterface $session): JsonResponse
    {
        try {
            $associationId = $session->get('association_id');
            // First find the event
            $event = $entityManager->getRepository(Event::class)->find($id);
            
            if (!$event) {
                throw new \Exception('Event not found');
            }
    
            // Check association access
            $association = $event->getAssociation();
            if (!$association || $association->getId() != $associationId) {
                throw new \Exception('Unauthorized access to this event');
            }
    
            // Get donations
            $donations = $entityManager->getRepository(Donation::class)
                ->createQueryBuilder('d')
                ->select('d')
                ->where('d.event = :event')
                ->setParameter('event', $event)
                ->getQuery()
                ->getResult();
    
            $donors = [];
            /** @var Donation $donation */
            foreach ($donations as $donation) {
                $donors[] = [
                    'name' => $donation->getFirstName() . ' ' . $donation->getLastName(),
                    'email' => $donation->getEmail(),
                    'amount' => (float)$donation->getDonationAmount() // Convert to float
                ];
            }
    
            return new JsonResponse([
                'success' => true,
                'donors' => $donors,
                'eventName' => $event->getName()
            ]);
    
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500); // Changed to 500 to match the error
        }
    }
    
    #[Route('/association/event/{id}/send-email', name: 'association_event_send_email', methods: ['POST'])]
    public function sendEmailToDonors(
        Request $request,
        Event $event, 
        EntityManagerInterface $entityManager,
        EmailService $emailService,
        SessionInterface $session
    ): JsonResponse {
        try {
            $associationId = $session->get('association_id');
            if ($event->getAssociation()->getId() !== $associationId) {
                throw new \Exception('Unauthorized access to this event');
            }

            $data = json_decode($request->getContent(), true);
            $customMessage = $data['message'] ?? null;

            if (!$customMessage) {
                throw new \Exception('Please provide a message for the donors');
            }

            $donations = $entityManager->getRepository(Donation::class)
                ->findBy(['event' => $event]);

            $sentCount = 0;
            foreach ($donations as $donation) {
                $emailService->sendEventUpdateEmail(
                    $donation->getEmail(), 
                    [
                        'eventName' => $event->getName(),
                        'donorName' => $donation->getFirstName(),
                        'associationName' => $event->getAssociation()->getName(),
                        'customMessage' => $customMessage
                    ]
                );
                $sentCount++;
            }

            return new JsonResponse([
                'success' => true,
                'message' => "Emails sent successfully to {$sentCount} donors"
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }


    #[Route('/article', name: 'app_article')]
    public function association(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Création d'un nouvel article
        $blogPost = new Post();
        $form = $this->createForm(PostType::class, $blogPost);
        $form->handleRequest($request);

        // Vérification si le formulaire d'ajout est soumis
       
        
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();
        
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
            
                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                    $blogPost->setImage($newFilename); // ✅ Lier le fichier au Post
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image : ' . $e->getMessage());
                }
            }
            
        
            $entityManager->persist($blogPost);
            $entityManager->flush();
        
            return $this->redirectToRoute('app_article');
        }
        

        // Récupération des articles existants
        $blogPosts = $entityManager->getRepository(Post::class)->findAll();

        return $this->render('blog/blogback.html.twig', [
            'form' => $form->createView(),
            'blogPosts' => $blogPosts,
        ]);
    }


  #[Route('/article/{id}/edit', name: 'app_article_edit')]
    public function edit(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
          $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('images_directory'), $newFilename);
                $post->setImage($newFilename);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Article modifié avec succès.');
            return $this->redirectToRoute('app_article');
        }

        return $this->render('blog/edit.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }




 
    #[Route('/article/{id}/delete', name: 'app_article_delete', methods: ['POST'])]
    public function delete(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $post->getId(), $request->request->get('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();
            $this->addFlash('success', 'Article supprimé avec succès.');
        }

        return $this->redirectToRoute('app_article');
    }
    
}
