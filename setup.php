<?php
// Auto setup script for production deployment

echo "Starting SPMURI Backend Setup...\n";

// Check if .env exists, if not copy from .env.production
if (!file_exists('.env')) {
    if (file_exists('.env.production')) {
        copy('.env.production', '.env');
        echo "✅ Production .env file created\n";
    } else {
        echo "❌ No .env.production file found\n";
        exit(1);
    }
}

// Set proper permissions
if (is_dir('storage')) {
    chmod('storage', 0755);
    echo "✅ Storage permissions set\n";
}

if (is_dir('bootstrap/cache')) {
    chmod('bootstrap/cache', 0755);
    echo "✅ Bootstrap cache permissions set\n";
}

// Check if composer.json exists
if (file_exists('composer.json')) {
    echo "✅ Composer.json found\n";
} else {
    echo "❌ Composer.json not found\n";
}

echo "✅ Setup completed! Now run: composer install --no-dev\n";
echo "✅ Your API will be available at: https://yourdomain.com/api/\n";
?>