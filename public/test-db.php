<?php
echo "<h2>Path Debug Test</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Current Directory: " . __DIR__ . "<br>";
echo "Parent Directory: " . dirname(__DIR__) . "<br>";
echo "<br><strong>File exists check (spmuri_backend paths):</strong><br>";
echo "- ../spmuri_backend/vendor/autoload.php: " . (file_exists('../spmuri_backend/vendor/autoload.php') ? 'YES' : 'NO') . "<br>";
echo "- ../spmuri_backend/bootstrap/app.php: " . (file_exists('../spmuri_backend/bootstrap/app.php') ? 'YES' : 'NO') . "<br>";

echo "<br><strong>spmuri_backend directory listing:</strong><br>";
if(is_dir('../spmuri_backend')) {
    $backendFiles = scandir('../spmuri_backend');
    foreach($backendFiles as $file) {
        if($file != '.' && $file != '..') {
            echo "- " . $file . (is_dir('../spmuri_backend/' . $file) ? ' (DIR)' : '') . "<br>";
        }
    }
} else {
    echo "spmuri_backend directory not found<br>";
}

echo "<br><strong>Directory listing:</strong><br>";
$files = scandir('.');
foreach($files as $file) {
    if($file != '.' && $file != '..') {
        echo "- " . $file . (is_dir($file) ? ' (DIR)' : '') . "<br>";
    }
}

echo "<br><strong>Parent directory listing:</strong><br>";
$parentFiles = scandir('..');
foreach($parentFiles as $file) {
    if($file != '.' && $file != '..') {
        echo "- " . $file . (is_dir('../' . $file) ? ' (DIR)' : '') . "<br>";
    }
}

echo "<br><strong>Testing Laravel Bootstrap:</strong><br>";
try {
    if (file_exists('../spmuri_backend/vendor/autoload.php')) {
        require_once '../spmuri_backend/vendor/autoload.php';
        echo "✅ Autoload loaded<br>";
        
        if (file_exists('../spmuri_backend/bootstrap/app.php')) {
            $app = require_once '../spmuri_backend/bootstrap/app.php';
            echo "✅ Laravel app loaded<br>";
            
            // Try to get database config
            $kernel = $app->make('Illuminate\Contracts\Console\Kernel');
            $kernel->bootstrap();
            echo "✅ Laravel bootstrapped<br>";
            
            // Test ReturnSaleInvoice model
            echo "<br><strong>Model Tests:</strong><br>";
            $returnSaleModel = new App\Models\ReturnSaleInvoice();
            echo "ReturnSaleInvoice table: " . $returnSaleModel->getTable() . "<br>";
            
            $returnSaleProductModel = new App\Models\ReturnSaleInvoiceProduct();
            echo "ReturnSaleInvoiceProduct table: " . $returnSaleProductModel->getTable() . "<br>";
            
        } else {
            echo "❌ bootstrap/app.php not found in spmuri_backend<br>";
        }
    } else {
        echo "❌ vendor/autoload.php not found in spmuri_backend<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
}
?>