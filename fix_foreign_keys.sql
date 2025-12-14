-- Fix foreign key constraints to allow CASCADE DELETE

-- First check existing constraints
SHOW CREATE TABLE purchaseinvoiceproduct;

-- Drop and recreate foreign key with CASCADE
ALTER TABLE purchaseinvoiceproduct DROP FOREIGN KEY purchaseinvoiceproduct_productid_foreign;
ALTER TABLE purchaseinvoiceproduct ADD CONSTRAINT purchaseinvoiceproduct_productid_foreign 
FOREIGN KEY (productId) REFERENCES purchase_products(id) ON DELETE CASCADE;

-- If ready_product_stock_items table has foreign key, update it too
-- ALTER TABLE ready_product_stock_items DROP FOREIGN KEY ready_product_stock_items_raw_material_id_foreign;
-- ALTER TABLE ready_product_stock_items ADD CONSTRAINT ready_product_stock_items_raw_material_id_foreign 
-- FOREIGN KEY (raw_material_id) REFERENCES purchase_products(id) ON DELETE CASCADE;