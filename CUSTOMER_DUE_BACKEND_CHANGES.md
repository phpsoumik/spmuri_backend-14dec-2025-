# Customer Due Amount Backend Implementation

## Changes Made

### 1. Database Migration
- **File**: `database/migrations/2025_01_31_000000_add_current_due_amount_to_customers_table.php`
- **Purpose**: Add `current_due_amount` field to customer table
- **Field Type**: `decimal(15,2)` with default value 0

### 2. Customer Model Updates
- **File**: `app/Models/Customer.php`
- **Changes**:
  - Added `current_due_amount` to `$fillable` array
  - Added `current_due_amount` to `$casts` array
  - Added `calculateCurrentDue()` method
  - Updated `getCurrentDueAttribute()` method

### 3. Customer Controller Updates
- **File**: `app/Http/Controllers/CustomerController.php`
- **Changes**:
  - Added current_due_amount calculation in `createSingleCustomer()` method
  - Added current_due_amount calculation in `updateSingleCustomer()` method
  - Formula: `current_due_amount = last_due_amount - opening_advance_amount`

### 4. Migration Scripts
- **File**: `run_current_due_migration.php`
  - Custom script to add column and update existing records
- **File**: `run_current_due_migration.bat`
  - Batch file to run both Laravel migration and custom script

## Formula
```
current_due_amount = last_due_amount - opening_advance_amount
```

## Examples
- Last Due: 50000, Advance: 10000 → Current Due: 40000 (Customer owes 40000)
- Last Due: 30000, Advance: 50000 → Current Due: -20000 (Customer has 20000 advance)
- Last Due: 25000, Advance: 25000 → Current Due: 0 (Balanced)

## How to Run Migration

### Option 1: Using Laravel Artisan
```bash
cd d:\xampp\htdocs\SPMURI_BACKEND
php artisan migrate --path=database/migrations/2025_01_31_000000_add_current_due_amount_to_customers_table.php
php run_current_due_migration.php
```

### Option 2: Using Batch File
```bash
cd d:\xampp\htdocs\SPMURI_BACKEND
run_current_due_migration.bat
```

## API Response Changes
- Customer list API now includes `currentDueAmount` field
- Customer detail API includes calculated current due amount
- Frontend will receive proper due amount calculations

## Testing
1. Run migration scripts
2. Add/Edit customers with last due and advance amounts
3. Verify `current_due_amount` field is calculated correctly
4. Test frontend customer list shows proper due amounts
5. Test sale form includes customer's existing due in total calculation