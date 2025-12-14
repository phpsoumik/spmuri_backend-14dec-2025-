<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $users = DB::table('users')
        ->select('id', 'username', 'email', 'phone', 'status')
        ->get();
    
    echo "=== Existing Users in Database ===\n";
    foreach ($users as $user) {
        echo "ID: {$user->id}\n";
        echo "Username: {$user->username}\n";
        echo "Email: {$user->email}\n";
        echo "Phone: {$user->phone}\n";
        echo "Status: {$user->status}\n";
        echo "------------------------\n";
    }
    
    if ($users->count() == 0) {
        echo "No users found in database!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>