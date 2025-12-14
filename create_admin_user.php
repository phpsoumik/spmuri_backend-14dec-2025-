<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

try {
    // Check if admin user exists
    $adminExists = DB::table('users')->where('username', 'admin')->first();
    
    if (!$adminExists) {
        // Create admin user
        DB::table('users')->insert([
            'name' => 'Admin User',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'phone' => '01700000000',
            'roleId' => 1,
            'status' => 'true',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        echo "Admin user created successfully!\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
    } else {
        echo "Admin user already exists!\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>