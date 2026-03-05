-- Update KPI Master to match actual database codes
-- This will replace the incorrect codes with your actual codes

-- Clear existing data
DELETE FROM kpi_master;

-- Insert your actual KPI codes with proper sections
INSERT INTO kpi_master (kpi_code, section, kpi_group, kpi_description, weight_percentage, section_number, display_order) VALUES

-- Core Competencies (Section 1) - S1 codes
('S1.1', 'Core Competencies', 'Communication', 'Verbal Communication Skills', 3.33, 1, 1),
('S1.2', 'Core Competencies', 'Communication', 'Written Communication Skills', 3.33, 1, 2),
('S1.3', 'Core Competencies', 'Teamwork', 'Team Collaboration', 3.34, 1, 3),

-- KPI Achievement (Section 2) - 1.x series (Daily Sales)
('1.1.1', 'KPI Achievement', 'Daily Sales Operations', 'Sales Target Achievement', 5.00, 2, 4),
('1.1.2', 'KPI Achievement', 'Daily Sales Operations', 'Customer Service Quality', 5.00, 2, 5),
('1.1.3', 'KPI Achievement', 'Daily Sales Operations', 'Product Knowledge', 5.00, 2, 6),
('1.1.4', 'KPI Achievement', 'Daily Sales Operations', 'Sales Process Compliance', 5.00, 2, 7),

-- KPI Achievement (Section 2) - 1.2 series (Customer Relations)
('1.2.1', 'KPI Achievement', 'Customer Relations', 'Customer Satisfaction', 5.00, 2, 8),
('1.2.2', 'KPI Achievement', 'Customer Relations', 'Customer Retention', 5.00, 2, 9),
('1.2.3', 'KPI Achievement', 'Customer Relations', 'Complaint Resolution', 5.00, 2, 10),

-- KPI Achievement (Section 2) - 2.x series (Store Operations)
('2.1.1', 'KPI Achievement', 'Store Operations', 'Store Cleanliness', 10.00, 2, 11),
('2.1.2', 'KPI Achievement', 'Store Operations', 'Inventory Management', 10.00, 2, 12),

-- KPI Achievement (Section 2) - 3.x series (Personal Development)
('3.1.1', 'KPI Achievement', 'Personal Development', 'Training Completion', 3.75, 2, 13),
('3.1.2', 'KPI Achievement', 'Personal Development', 'Self-Improvement', 3.75, 2, 14),
('3.1.3', 'KPI Achievement', 'Personal Development', 'Goal Achievement', 3.75, 2, 15),
('3.1.4', 'KPI Achievement', 'Personal Development', 'Initiative Taking', 3.75, 2, 16),

-- KPI Achievement (Section 2) - 4.x series (Operational Excellence)
('4.1.1', 'KPI Achievement', 'Operational Excellence', 'Cost Control', 5.00, 2, 17),
('4.1.2', 'KPI Achievement', 'Operational Excellence', 'Time Management', 5.00, 2, 18),
('4.2.1', 'KPI Achievement', 'Operational Excellence', 'Process Improvement', 3.33, 2, 19),
('4.2.2', 'KPI Achievement', 'Operational Excellence', 'Quality Standards', 3.33, 2, 20),
('4.2.3', 'KPI Achievement', 'Operational Excellence', 'Safety Compliance', 3.34, 2, 21);

-- Verify insertion
SELECT COUNT(*) as total_kpis FROM kpi_master;
SELECT kpi_code, section_number, kpi_group FROM kpi_master ORDER BY display_order;
