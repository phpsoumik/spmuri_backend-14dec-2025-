<?php

require_once 'vendor/autoload.php';

use Illuminate\Http\Request;
use App\Models\DailyIncome;

// Simple update endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID required']);
        exit;
    }
    
    try {
        $dailyIncome = DailyIncome::find($id);
        
        if (!$dailyIncome) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Daily income not found']);
            exit;
        }
        
        $dailyIncome->update([
            'customer_name' => $input['customerName'],
            'date' => $input['date'],
            'amount' => $input['amount'],
            'purpose' => $input['purpose']
        ]);
        
        echo json_encode([
            'success' => true,
            'data' => $dailyIncome,
            'message' => 'Daily income updated successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update daily income',
            'error' => $e->getMessage()
        ]);
    }
}
?>