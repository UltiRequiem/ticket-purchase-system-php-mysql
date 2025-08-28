<?php
namespace TicketFairy\Controllers;

use TicketFairy\Services\TicketService;
use TicketFairy\Exceptions\TicketException;

class TicketController {
    private $ticketService;
    
    public function __construct(TicketService $ticketService) {
        $this->ticketService = $ticketService;
    }
    
    public function purchase(): void {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON']);
                return;
            }
            
            $result = $this->ticketService->purchaseTickets(
                $input['event_id'] ?? null,
                $input['customer_name'] ?? '',
                $input['customer_email'] ?? '',
                $input['quantity'] ?? 0
            );
            
            http_response_code(201);
            echo json_encode($result);
            
        } catch (TicketException $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Internal server error'
            ]);
        }
    }
}