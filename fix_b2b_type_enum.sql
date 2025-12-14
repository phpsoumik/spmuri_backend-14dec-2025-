-- Fix B2B Companies type enum to include 'sub' option
ALTER TABLE b2b_companies MODIFY COLUMN type ENUM('main', 'independent', 'sub') DEFAULT 'independent';