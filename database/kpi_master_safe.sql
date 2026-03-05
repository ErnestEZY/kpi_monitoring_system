-- KPI Master Data - Safe Import Version
-- This version handles foreign key constraints properly

-- First, check what kpi_codes exist in kpi_scores
-- (Run this query in phpMyAdmin to see what codes need to be added)
SELECT DISTINCT kpi_code FROM kpi_scores ORDER BY kpi_code;

-- Then insert only the codes that exist in your data
-- Use INSERT IGNORE to avoid conflicts with existing records

INSERT IGNORE INTO kpi_master (kpi_code, section, kpi_group, kpi_description, weight_percentage, section_number, display_order) VALUES
-- Core Competencies (Section 1)
('CC1', 'Core Competencies', 'Communication', 'Verbal Communication Skills', 3.33, 1, 1),
('CC2', 'Core Competencies', 'Communication', 'Written Communication Skills', 3.33, 1, 2),
('CC3', 'Core Competencies', 'Teamwork', 'Team Collaboration', 3.34, 1, 3),

-- KPI Achievement - Sales Operations (Section 2)
('KPI1', 'KPI Achievement', 'Daily Sales Operations', 'Sales Target Achievement', 5.00, 2, 1),
('KPI2', 'KPI Achievement', 'Daily Sales Operations', 'Customer Service Quality', 5.00, 2, 2),
('KPI3', 'KPI Achievement', 'Daily Sales Operations', 'Product Knowledge', 5.00, 2, 3),
('KPI4', 'KPI Achievement', 'Daily Sales Operations', 'Sales Process Compliance', 5.00, 2, 4),

-- KPI Achievement - Customer Relations (Section 2)
('KPI5', 'KPI Achievement', 'Customer Relations', 'Customer Satisfaction', 5.00, 2, 5),
('KPI6', 'KPI Achievement', 'Customer Relations', 'Customer Retention', 5.00, 2, 6),
('KPI7', 'KPI Achievement', 'Customer Relations', 'Complaint Resolution', 5.00, 2, 7),

-- KPI Achievement - Store Operations (Section 2)
('KPI8', 'KPI Achievement', 'Store Operations', 'Store Cleanliness', 10.00, 2, 8),
('KPI9', 'KPI Achievement', 'Store Operations', 'Inventory Management', 10.00, 2, 9),

-- KPI Achievement - Personal Development (Section 2)
('KPI10', 'KPI Achievement', 'Personal Development', 'Training Completion', 3.75, 2, 10),
('KPI11', 'KPI Achievement', 'Personal Development', 'Self-Improvement', 3.75, 2, 11),
('KPI12', 'KPI Achievement', 'Personal Development', 'Goal Achievement', 3.75, 2, 12),
('KPI13', 'KPI Achievement', 'Personal Development', 'Initiative Taking', 3.75, 2, 13),

-- KPI Achievement - Operational Excellence (Section 2)
('KPI14', 'KPI Achievement', 'Operational Excellence', 'Cost Control', 5.00, 2, 14),
('KPI15', 'KPI Achievement', 'Operational Excellence', 'Time Management', 5.00, 2, 15),
('KPI16', 'KPI Achievement', 'Operational Excellence', 'Process Improvement', 3.33, 2, 16),
('KPI17', 'KPI Achievement', 'Operational Excellence', 'Quality Standards', 3.33, 2, 17),
('KPI18', 'KPI Achievement', 'Operational Excellence', 'Safety Compliance', 3.34, 2, 18);

-- Verify insertion
SELECT COUNT(*) as total_kpis FROM kpi_master;
SELECT * FROM kpi_master ORDER BY display_order;
