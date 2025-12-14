<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ImportCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:customers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import customers from predefined list';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $customers = [
            'AMAR GOLUI', 'SAKTI NATH KHA', 'AMAR SAHA', 'SAMANTA MURGI',
            'AMIT GHOSE', 'SANDIP KHARA', 'ANANDA NANDI', 'SANJAY DEY',
            'ASHIM PATRA', 'SANTANU JANA', 'ASHOKE MOLLIK', 'SANTU POLLEY',
            'ASHOKE POLLEY', 'SHIB SHANKAR SHAW', 'BABLU SAMANTA', 'SHITAL SHAW',
            'BAPPA MONDAL', 'SOURAV SADHUKHA', 'BINOD SHAW', 'SRIKANTA SINGH',
            'BIPIN DHARA', 'SUBHRA POLLEY', 'BISWAJIT SAHA', 'SUKUMAR SADHUKA',
            'DEBASISH KARAR', 'SURESH SHAW 1', 'DILIP SHAW', 'SWAPAN MAJI',
            'HALU DA', 'TAPAN PATRA', 'JHORO DA', 'TULU SOMU',
            'KARTIK DAS', 'UTTAM SHEET', 'KHOKON KUNDU', 'MADAN MOHAN GHOSH',
            'NANDI BROTHERS', 'NAYAN DUTTA', 'NIMAI DEYASI', 'PULOK SIL',
            'RAMPRASAD SHEET'
        ];

        $bar = $this->output->createProgressBar(count($customers));
        $bar->start();

        $this->info('Importing customers...');
        
        $count = 0;
        foreach ($customers as $name) {
            $nameParts = explode(' ', $name);
            $firstName = $nameParts[0];
            $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';
            
            // Check if customer already exists
            $exists = Customer::where('username', $name)->exists();
            
            if (!$exists) {
                Customer::create([
                    'username' => $name,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'password' => Hash::make('password'),
                    'status' => 'true',
                ]);
                $count++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Imported $count new customers successfully!");
    }
}