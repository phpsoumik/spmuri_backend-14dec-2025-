<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../vendor/autoload.php';

$app = require_once '../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DailyIncome;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        $id = $input['id'] ?? null;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID required']);
            exit;
        }
        
        $dailyIncome = DailyIncome::find($id);
        
        if (!$dailyIncome) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Daily income not found']);
            exit;
        }
        
        $updated = $dailyIncome->update([
            'customer_name' => $input['customerName'],
            'date' => $input['date'],
            'amount' => $input['amount'],
            'purpose' => $input['purpose']
        ]);
        
        if (!$updated) {
            throw new Exception('Update failed');
        }
        
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