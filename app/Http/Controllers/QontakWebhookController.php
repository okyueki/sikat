<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\WhatsappLog;

class QontakWebhookController extends Controller
{
    /**
     * Handle incoming webhook from Qontak for message status updates
     */
    public function handleStatusUpdate(Request $request)
    {
        $payload = $request->all();

        Log::info('Qontak webhook received', ['payload' => $payload]);

        // Validate required fields
        if (!isset($payload['id'])) {
            Log::warning('Qontak webhook missing message ID', ['payload' => $payload]);
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $qontakMessageId = $payload['id'];
        
        // Find the log entry by qontak_message_id
        $log = WhatsappLog::where('qontak_message_id', $qontakMessageId)->first();

        if (!$log) {
            Log::warning("Qontak webhook: Message not found for ID: {$qontakMessageId}");
            return response()->json(['message' => 'Message not found'], 404);
        }

        // Extract status information from webhook payload
        $executeStatus = $payload['execute_status'] ?? $log->delivery_status;
        $messageStatus = $payload['message_status_count'] ?? [];

        // Determine final delivery status
        $deliveryStatus = $this->determineDeliveryStatus($executeStatus, $messageStatus);

        // Update timestamps based on status
        $deliveredAt = $log->delivered_at;
        $readAt = $log->read_at;

        if ($deliveryStatus === 'delivered' && !$deliveredAt) {
            $deliveredAt = now();
        }
        if ($deliveryStatus === 'read' && !$readAt) {
            $readAt = now();
            if (!$deliveredAt) {
                $deliveredAt = now();
            }
        }

        // Update the log
        $log->update([
            'delivery_status' => $deliveryStatus,
            'delivery_response' => json_encode($payload),
            'delivered_at' => $deliveredAt,
            'read_at' => $readAt,
        ]);

        Log::info("Qontak webhook: Updated status for {$qontakMessageId} to {$deliveryStatus}");

        return response()->json([
            'message' => 'Status updated',
            'log_id' => $log->id,
            'delivery_status' => $deliveryStatus
        ]);
    }

    /**
     * Determine final delivery status from webhook data
     */
    protected function determineDeliveryStatus(string $executeStatus, array $messageStatus): string
    {
        // Priority: failed > read > delivered > sent > execute_status
        if (!empty($messageStatus['failed']) && $messageStatus['failed'] > 0) {
            return 'failed';
        }
        if (!empty($messageStatus['read']) && $messageStatus['read'] > 0) {
            return 'read';
        }
        if (!empty($messageStatus['delivered']) && $messageStatus['delivered'] > 0) {
            return 'delivered';
        }
        if (!empty($messageStatus['sent']) && $messageStatus['sent'] > 0) {
            return 'sent';
        }
        
        return $executeStatus;
    }

    /**
     * Handle message failed webhook
     */
    public function handleFailed(Request $request)
    {
        $payload = $request->all();
        
        Log::info('Qontak webhook: Message failed', ['payload' => $payload]);

        if (!isset($payload['id'])) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $log = WhatsappLog::where('qontak_message_id', $payload['id'])->first();

        if ($log) {
            $log->update([
                'delivery_status' => 'failed',
                'delivery_response' => json_encode($payload),
            ]);
        }

        return response()->json(['message' => 'Failed status recorded']);
    }

    /**
     * Handle message delivered webhook
     */
    public function handleDelivered(Request $request)
    {
        $payload = $request->all();
        
        Log::info('Qontak webhook: Message delivered', ['payload' => $payload]);

        if (!isset($payload['id'])) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $log = WhatsappLog::where('qontak_message_id', $payload['id'])->first();

        if ($log && !$log->delivered_at) {
            $log->update([
                'delivery_status' => 'delivered',
                'delivered_at' => now(),
                'delivery_response' => json_encode($payload),
            ]);
        }

        return response()->json(['message' => 'Delivered status recorded']);
    }

    /**
     * Handle message read webhook
     */
    public function handleRead(Request $request)
    {
        $payload = $request->all();
        
        Log::info('Qontak webhook: Message read', ['payload' => $payload]);

        if (!isset($payload['id'])) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $log = WhatsappLog::where('qontak_message_id', $payload['id'])->first();

        if ($log && !$log->read_at) {
            $deliveredAt = $log->delivered_at ?? now();
            $log->update([
                'delivery_status' => 'read',
                'delivered_at' => $deliveredAt,
                'read_at' => now(),
                'delivery_response' => json_encode($payload),
            ]);
        }

        return response()->json(['message' => 'Read status recorded']);
    }

    /**
     * Webhook health check endpoint
     */
    public function healthCheck()
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'Qontak Webhook Handler',
            'timestamp' => now()->toIso8601String()
        ]);
    }
}
