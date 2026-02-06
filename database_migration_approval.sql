-- Migration: Add Admin Approval Tracking to Customer Provider Selections
-- Date: 2025-12-10
-- Description: Adds columns to track admin approval status for provider selections

USE s24_services;

-- Add new columns for admin approval tracking
ALTER TABLE customer_provider_selections
ADD COLUMN admin_approved BOOLEAN DEFAULT FALSE AFTER status,
ADD COLUMN admin_approved_at TIMESTAMP NULL AFTER provider_responded_at,
ADD COLUMN approved_by_admin_id INT NULL AFTER admin_approved_at,
ADD INDEX idx_admin_approved (admin_approved, admin_approved_at);

-- Update existing selections to have admin_approved = FALSE
UPDATE customer_provider_selections 
SET admin_approved = FALSE 
WHERE admin_approved IS NULL;

-- Display success message
SELECT 'Migration completed successfully!' as status;
SELECT COUNT(*) as total_selections, 
       SUM(CASE WHEN admin_approved = TRUE THEN 1 ELSE 0 END) as approved_selections,
       SUM(CASE WHEN admin_approved = FALSE THEN 1 ELSE 0 END) as pending_selections
FROM customer_provider_selections;
