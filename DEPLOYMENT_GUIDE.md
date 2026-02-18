# Deployment Guide - Total Calculation Migration

## Problem
Live server e purano sale invoices ache jegulo te `total_calculation` field null/0 ache. Notun code e `total_calculation` field use hocche, tai purano data show hobe na.

## Solution
Backward compatibility add kora hoyeche + data migration script create kora hoyeche.

## Deployment Steps (Live Server e)

### Step 1: Backup Database
```bash
# Database backup nao before migration
mysqldump -u username -p database_name > backup_before_migration.sql
```

### Step 2: Pull Latest Code
```bash
git pull origin main
```

### Step 3: Run Migrations
```bash
# First migration: Add total_calculation column
php artisan migrate --path=database/migrations/2025_02_03_000000_add_total_calculation_to_sale_invoice_table.php

# Second migration: Populate existing data
php artisan migrate --path=database/migrations/2025_02_03_000001_populate_total_calculation_for_existing_invoices.php
```

### Step 4: Verify Data
```sql
-- Check if total_calculation is populated
SELECT id, totalAmount, total_calculation 
FROM saleinvoice 
WHERE total_calculation = 0 OR total_calculation IS NULL
LIMIT 10;
```

### Step 5: Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## Rollback (If Needed)
```bash
php artisan migrate:rollback --step=1
```

## How It Works

### Backend Changes:
1. **Backward Compatibility**: CustomerController e fallback logic add kora hoyeche
   - Jodi `total_calculation` null/0 hoy, tahole transaction theke calculate korbe
   - Jodi transaction e na thake, tahole `totalAmount` field use korbe

2. **Data Migration**: Existing invoices er jonno `total_calculation` populate korbe
   - Transaction table theke amount niye update korbe
   - Jodi transaction na thake, totalAmount field use korbe

### Frontend Changes:
- Kono change lagbe na, backend theke thik data ashbe

## Testing Checklist
- [ ] Purano invoices properly show hocche
- [ ] Notun invoices properly save hocche
- [ ] Transaction list thik show hocche
- [ ] Total amount calculation thik ache

## Notes
- Migration automatic purano data populate kore debe
- Backend e fallback logic ache, tai jodi kono data miss hoy tahole o kaj korbe
- Live e deploy korar age staging/test server e test koro
