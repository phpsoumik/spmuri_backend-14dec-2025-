-- Fix expenses table structure
ALTER TABLE expenses MODIFY COLUMN category VARCHAR(100) NOT NULL;
ALTER TABLE expenses ADD COLUMN IF NOT EXISTS quantity_kg DECIMAL(8,3) NULL;
ALTER TABLE expenses ADD COLUMN IF NOT EXISTS rate_per_kg DECIMAL(8,2) NULL;

-- Show table structure to verify
DESCRIBE expenses;