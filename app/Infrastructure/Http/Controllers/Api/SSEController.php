<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SSEController extends Controller
{
    private const CACHE_PREFIX = 'sse_events:';
    private const MAX_RETRY_TIME = 3000;
    private const HEARTBEAT_INTERVAL = 30000;

    public function stream(Request $request): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($request) {
            $this->handleSSEConnection($request);
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', 'Cache-Control');

        return $response;
    }

    private function handleSSEConnection(Request $request): void
    {
        $clientId = $request->query('client_id', uniqid());
        $lastEventId = $request->header('Last-Event-ID', '0');

        Log::info('SSE connection established', ['client_id' => $clientId]);

        // Send initial connection event
        $this->sendSSEEvent('connected', [
            'client_id' => $clientId,
            'timestamp' => now()->toISOString(),
        ]);

        // Mark all existing events as seen for this client if it's a new connection
        if ($lastEventId === '0') {
            $this->markExistingEventsAsSeen($clientId);
        }

        $lastHeartbeat = time();

        while (connection_status() === CONNECTION_NORMAL && !connection_aborted()) {
            // Check for new events
            $events = $this->getEventsAfter($lastEventId, $clientId);
            
            foreach ($events as $event) {
                $this->sendSSEEvent($event['type'], $event['data'], $event['id']);
                $lastEventId = $event['id'];
            }

            // Send heartbeat every 30 seconds
            if (time() - $lastHeartbeat > 30) {
                $this->sendHeartbeat();
                $lastHeartbeat = time();
            }

            // Check every second
            sleep(1);

            // Flush output to ensure real-time delivery
            if (ob_get_level()) {
                ob_flush();
            }
            flush();
        }

        Log::info('SSE connection closed', ['client_id' => $clientId]);
    }

    private function sendSSEEvent(string $event, array $data, ?string $id = null): void
    {
        if ($id) {
            echo "id: {$id}\n";
        }
        
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n";
        echo "retry: " . self::MAX_RETRY_TIME . "\n";
        echo "\n";

        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }

    private function sendHeartbeat(): void
    {
        $this->sendSSEEvent('heartbeat', [
            'timestamp' => now()->toISOString(),
        ]);
    }

    private function getEventsAfter(string $lastEventId, string $clientId): array
    {
        // Get events from cache that are newer than lastEventId
        $allEvents = Cache::get(self::CACHE_PREFIX . 'events', []);
        
        // Filter events that come after the last received event ID
        $newEvents = array_filter($allEvents, function ($event) use ($lastEventId) {
            return $event['id'] > $lastEventId;
        });
        
        // Get client seen events
        $seenEvents = Cache::get(self::CACHE_PREFIX . 'seen:' . $clientId, []);
        
        // Filter out events that this client has already seen
        $unseenEvents = array_filter($newEvents, function ($event) use ($seenEvents) {
            return !in_array($event['id'], $seenEvents);
        });
        
        // Only return events that are less than 10 minutes old to prevent very old spam
        $tenMinutesAgo = now()->subMinutes(10)->timestamp;
        return array_filter($unseenEvents, function ($event) use ($tenMinutesAgo) {
            return $event['created_at'] > $tenMinutesAgo;
        });
    }
    
    private function markExistingEventsAsSeen(string $clientId): void
    {
        $allEvents = Cache::get(self::CACHE_PREFIX . 'events', []);
        $eventIds = array_column($allEvents, 'id');
        
        // Store seen events for this client for 1 hour
        Cache::put(self::CACHE_PREFIX . 'seen:' . $clientId, $eventIds, 3600);
        
        Log::info('Marked existing events as seen for client', [
            'client_id' => $clientId,
            'event_count' => count($eventIds)
        ]);
    }

    public static function broadcastEvent(string $type, array $data): void
    {
        $eventId = (string) (microtime(true) * 10000);
        
        $event = [
            'id' => $eventId,
            'type' => $type,
            'data' => array_merge($data, [
                'timestamp' => now()->toISOString(),
            ]),
            'created_at' => now()->timestamp,
        ];

        // Store event in cache for SSE clients
        $events = Cache::get(self::CACHE_PREFIX . 'events', []);
        
        // Remove events older than 1 hour before adding new one
        $oneHourAgo = now()->subHour()->timestamp;
        $events = array_filter($events, function ($existingEvent) use ($oneHourAgo) {
            return $existingEvent['created_at'] > $oneHourAgo;
        });
        
        $events[] = $event;

        // Keep only last 50 events to prevent memory issues
        if (count($events) > 50) {
            $events = array_slice($events, -50);
        }

        // Store events for 1 hour
        Cache::put(self::CACHE_PREFIX . 'events', $events, 3600);

        Log::info('SSE event broadcasted', [
            'type' => $type,
            'event_id' => $eventId,
            'data' => $data,
            'total_events_in_cache' => count($events),
        ]);
    }

    public function testEvent(Request $request): JsonResponse
    {
        self::broadcastEvent('test', [
            'message' => 'This is a test event',
            'data' => $request->all(),
        ]);

        return response()->json([
            'message' => 'Test event broadcasted successfully',
        ]);
    }

    public function clearEvents(): JsonResponse
    {
        Cache::forget(self::CACHE_PREFIX . 'events');
        
        return response()->json([
            'message' => 'Events cache cleared successfully',
        ]);
    }
}