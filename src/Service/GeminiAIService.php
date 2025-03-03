<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Event;

class GeminiAIService
{
    private $client;
    private $apiKey;
    private $entityManager;
    private const API_URL = 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent';

    public function __construct(HttpClientInterface $client, EntityManagerInterface $entityManager)
    {
        $this->client = $client;
        $this->entityManager = $entityManager;
        $this->apiKey = trim($_ENV['GEMINI_API_KEY']);
        
        error_log('API KEY STATUS: ' . (empty($this->apiKey) ? 'MISSING' : 'SET'));
        if (!empty($this->apiKey)) {
            error_log('API KEY FIRST 5 CHARS: ' . substr($this->apiKey, 0, 5));
        }
    }

    private function getEventNames(): array
    {
        $events = $this->entityManager->getRepository(Event::class)->findAll();
        return array_map(fn($event) => $event->getName(), $events);
    }

    private function getEventDetails(string $eventName): ?array
    {
        $event = $this->entityManager->getRepository(Event::class)->findOneBy(['name' => $eventName]);
        if (!$event) {
            return null;
        }

        return [
            'name' => $event->getName(),
            'date' => $event->getEventDate()->format('Y-m-d'),
            'price' => $event->getPrice(),
            'location' => $event->getLocation()->getName(),
            'association' => $event->getAssociation()->getName(),
            'type' => $event->getType(),
            'image' => $event->getImageFilename()
        ];
    }

    private function getNearestEvent(): ?array
    {
        $event = $this->entityManager->getRepository(Event::class)->createQueryBuilder('e')
            ->where('e.eventDate >= :today')
            ->setParameter('today', new \DateTime())
            ->orderBy('e.eventDate', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$event) {
            return null;
        }

        return [
            'name' => $event->getName(),
            'date' => $event->getEventDate()->format('Y-m-d'),
            'price' => $event->getPrice(),
            'location' => $event->getLocation()->getName(),
            'association' => $event->getAssociation()->getName(),
            'type' => $event->getType(),
            'image' => $event->getImageFilename()
        ];
    }

    private function getEventsByLocation(string $location): array
    {
        $events = $this->entityManager->getRepository(Event::class)->createQueryBuilder('e')
            ->where('e.location = :location')
            ->setParameter('location', $location)
            ->getQuery()
            ->getResult();

        return array_map(function ($event) {
            return [
                'name' => $event->getName(),
                'date' => $event->getEventDate()->format('Y-m-d'),
                'price' => $event->getPrice(),
                'location' => $event->getLocation()->getName(),
                'association' => $event->getAssociation()->getName(),
                'type' => $event->getType(),
                'image' => $event->getImageFilename()
            ];
        }, $events);
    }

    private function getEventsByBudget(float $budget): array
    {
        $events = $this->entityManager->getRepository(Event::class)->createQueryBuilder('e')
            ->where('e.price <= :budget')
            ->setParameter('budget', $budget)
            ->getQuery()
            ->getResult();

        return array_map(function ($event) {
            return [
                'name' => $event->getName(),
                'date' => $event->getEventDate()->format('Y-m-d'),
                'price' => $event->getPrice(),
                'location' => $event->getLocation()->getName(),
                'association' => $event->getAssociation()->getName(),
                'type' => $event->getType(),
                'image' => $event->getImageFilename()
            ];
        }, $events);
    }

    public function askQuestion(string $question): string
    {
        $eventNames = $this->getEventNames();
        $eventNamesList = implode(", ", $eventNames);

        $context = "You are an AI assistant for EcoDon, an eco-friendly donation platform. " .
                  "Our mission is to facilitate donations of clothes, food, and other items while promoting environmental sustainability. " .
                  "We accept various types of donations including clothes, food, furniture, and electronics. " .
                  "Our platform connects donors and associations with those in need while minimizing waste and environmental impact. " .
                  "Here are the current events: " . $eventNamesList . ". " .
                  "Please provide helpful, friendly responses related to donations, environmental impact, and our services. " .
                  "Question: " . $question;

        // Check if the question is about the nearest event
        if (stripos($question, 'nearest event') !== false) {
            $nearestEvent = $this->getNearestEvent();
            if ($nearestEvent) {
                $context .= " The nearest event is '{$nearestEvent['name']}' on {$nearestEvent['date']} at {$nearestEvent['location']}.";
            } else {
                $context .= " There are no upcoming events.";
            }
        }

        // Check if the question is about a specific event
        foreach ($eventNames as $eventName) {
            if (stripos($question, $eventName) !== false) {
                $eventDetails = $this->getEventDetails($eventName);
                if ($eventDetails) {
                    $context .= " Here are the details for the event '{$eventName}': " .
                                "Name: {$eventDetails['name']}, " .
                                "Date: {$eventDetails['date']}, " .
                                "Price: {$eventDetails['price']}, " .
                                "Location: {$eventDetails['location']}, " .
                                "Association: {$eventDetails['association']}, " .
                                "Type: {$eventDetails['type']}.";
                }
                break;
            }
        }

        // Check if the question is about events in a specific location
        if (preg_match('/events in (.+)/i', $question, $matches)) {
            $location = $matches[1];
            $events = $this->getEventsByLocation($location);
            if (!empty($events)) {
                $context .= " Here are the events in {$location}: ";
                foreach ($events as $event) {
                    $context .= "Event: {$event['name']}, Date: {$event['date']}, Location: {$event['location']}, Association: {$event['association']}, Type: {$event['type']}. ";
                }
            } else {
                $context .= " Sorry, there are no events in {$location}.";
            }
        }

        // Check if the question is about events within a specific budget
        if (preg_match('/events under (\d+)/i', $question, $matches)) {
            $budget = (float)$matches[1];
            $events = $this->getEventsByBudget($budget);
            if (!empty($events)) {
                $context .= " Here are the events under {$budget}: ";
                foreach ($events as $event) {
                    $context .= "Event: {$event['name']}, Date: {$event['date']}, Price: {$event['price']}, Location: {$event['location']}, Association: {$event['association']}, Type: {$event['type']}. ";
                }
            } else {
                $context .= " Sorry, there are no events under {$budget}.";
            }
        }

        try {
            error_log('Preparing request to Gemini API with URL: ' . self::API_URL);
            
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $context]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 250,
                    'topK' => 40,
                    'topP' => 0.95,
                ]
            ];
            
            error_log('Sending request to Gemini API with payload: ' . json_encode($payload));
            
            $response = $this->client->request('POST', self::API_URL . '?key=' . $this->apiKey, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 15
            ]);
            
            $statusCode = $response->getStatusCode();
            error_log('Gemini API response status code: ' . $statusCode);
            
            if ($statusCode !== 200) {
                $content = $response->getContent(false);
                error_log('Gemini API error response: ' . $content);
                throw new \Exception('API returned status code: ' . $statusCode);
            }
            
            $data = $response->toArray();
            error_log('Gemini API response structure: ' . json_encode(array_keys($data)));
            
            if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                error_log('Unexpected response structure: ' . json_encode($data));
                throw new \Exception('Unexpected API response structure');
            }
            
            $response = $data['candidates'][0]['content']['parts'][0]['text'];
            return trim($response);

        } catch (\Exception $e) {
            error_log('Gemini AI Error: ' . $e->getMessage());
            error_log('Gemini AI Error Stack Trace: ' . $e->getTraceAsString());
            throw new \Exception('Failed to get response from Gemini API: ' . $e->getMessage());
        }
    }
}