-- ============================================================================
-- UPDATED SAMPLE DATA FOR KPI MONITORING SYSTEM
-- This file contains realistic data optimized for training recommendations
-- ============================================================================

-- Clear existing data
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE kpi_scores;
TRUNCATE TABLE staff_comments;
TRUNCATE TABLE staff;
TRUNCATE TABLE supervisors;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- SUPERVISORS DATA
-- ============================================================================
INSERT INTO supervisors (supervisor_id, username, password, full_name, email, status, created_at) VALUES
(1, 'admin', 'admin123', 'John Anderson', 'john.anderson@store.com', 'Active', '2024-01-15 09:00:00'),
(2, 'manager1', 'pass123', 'Sarah Williams', 'sarah.w@store.com', 'Active', '2024-03-20 11:30:00'),
(3, 'super1', 'super123', 'Michael Chen', 'michael.c@store.com', 'Active', '2024-06-10 13:45:00');

-- ============================================================================
-- STAFF DATA (13 Sales Assistants)
-- ============================================================================
INSERT INTO staff (staff_id, staff_code, name, email, department, position, status, hire_date, created_at) VALUES
(1, 'SA001', 'Alice Johnson', 'alice.j@store.com', 'Electronics', 'Sales Assistant', 'Active', '2020-01-15', '2020-01-15 09:00:00'),
(2, 'SA002', 'Bob Smith', 'bob.s@store.com', 'Fashion', 'Sales Assistant', 'Active', '2019-06-20', '2019-06-20 10:30:00'),
(3, 'SA003', 'Carol Martinez', 'carol.m@store.com', 'Home Goods', 'Sales Assistant', 'Active', '2021-03-10', '2021-03-10 11:15:00'),
(4, 'SA004', 'David Lee', 'david.l@store.com', 'Electronics', 'Sales Assistant', 'Active', '2020-09-05', '2020-09-05 14:20:00'),
(5, 'SA005', 'Emma Wilson', 'emma.w@store.com', 'Fashion', 'Sales Assistant', 'Active', '2022-01-12', '2022-01-12 08:45:00'),
(6, 'SA006', 'Frank Chen', 'frank.c@store.com', 'Electronics', 'Sales Assistant', 'Active', '2021-07-18', '2021-07-18 15:30:00'),
(7, 'SA007', 'Grace Taylor', 'grace.t@store.com', 'Home Goods', 'Sales Assistant', 'Active', '2020-11-22', '2020-11-22 10:00:00'),
(8, 'SA008', 'Henry Brown', 'henry.b@store.com', 'Fashion', 'Sales Assistant', 'Active', '2019-04-15', '2019-04-15 13:20:00'),
(9, 'SA009', 'Iris Wang', 'iris.w@store.com', 'Electronics', 'Sales Assistant', 'Active', '2021-09-30', '2021-09-30 09:45:00'),
(10, 'SA010', 'Jack Miller', 'jack.m@store.com', 'Home Goods', 'Sales Assistant', 'Active', '2020-05-08', '2020-05-08 11:30:00'),
(11, 'SA011', 'Karen Davis', 'karen.d@store.com', 'Fashion', 'Sales Assistant', 'Active', '2022-02-14', '2022-02-14 14:15:00'),
(12, 'SA012', 'Leo Garcia', 'leo.g@store.com', 'Electronics', 'Sales Assistant', 'Active', '2021-12-01', '2021-12-01 10:20:00'),
(13, 'SA013', 'Maria Rodriguez', 'maria.r@store.com', 'Home Goods', 'Sales Assistant', 'Active', '2020-08-25', '2020-08-25 16:00:00');

-- ============================================================================
-- KPI SCORES FOR 2022
-- Varied performance to trigger different training recommendations
-- ============================================================================

-- SA001 - Alice Johnson (High Performer - needs Leadership training)
INSERT INTO kpi_scores (staff_id, kpi_code, evaluation_date, evaluation_year, score, weighted_score) VALUES
(1, 'CC1', '2022-12-31', 2022, 4.8, 0.1598), (1, 'CC2', '2022-12-31', 2022, 4.5, 0.1499),
(1, 'CC3', '2022-12-31', 2022, 4.7, 0.1570), (1, 'KPI1', '2022-12-31', 2022, 4.9, 0.2450),
(1, 'KPI2', '2022-12-31', 2022, 4.6, 0.2300), (1, 'KPI3', '2022-12-31', 2022, 4.8, 0.2400),
(1, 'KPI4', '2022-12-31', 2022, 4.7, 0.2350), (1, 'KPI5', '2022-12-31', 2022, 4.5, 0.2250),
(1, 'KPI6', '2022-12-31', 2022, 4.6, 0.2300), (1, 'KPI7', '2022-12-31', 2022, 4.4, 0.2200),
(1, 'KPI8', '2022-12-31', 2022, 4.8, 0.4800), (1, 'KPI9', '2022-12-31', 2022, 4.7, 0.4700),
(1, 'KPI10', '2022-12-31', 2022, 4.5, 0.1688), (1, 'KPI11', '2022-12-31', 2022, 4.6, 0.1725),
(1, 'KPI12', '2022-12-31', 2022, 4.4, 0.1650), (1, 'KPI13', '2022-12-31', 2022, 4.5, 0.1688),
(1, 'KPI14', '2022-12-31', 2022, 4.3, 0.2150), (1, 'KPI15', '2022-12-31', 2022, 4.4, 0.2200),
(1, 'KPI16', '2022-12-31', 2022, 4.6, 0.1533), (1, 'KPI17', '2022-12-31', 2022, 4.5, 0.1500),
(1, 'KPI18', '2022-12-31', 2022, 4.4, 0.1467);

-- SA002 - Bob Smith (Weak in Customer Service - needs Customer Service training)
INSERT INTO kpi_scores (staff_id, kpi_code, evaluation_date, evaluation_year, score, weighted_score) VALUES
(2, 'CC1', '2022-12-31', 2022, 3.5, 0.1166), (2, 'CC2', '2022-12-31', 2022, 3.2, 0.1066),
(2, 'CC3', '2022-12-31', 2022, 3.4, 0.1136), (2, 'KPI1', '2022-12-31', 2022, 3.8, 0.1900),
(2, 'KPI2', '2022-12-31', 2022, 3.6, 0.1800), (2, 'KPI3', '2022-12-31', 2022, 3.7, 0.1850),
(2, 'KPI4', '2022-12-31', 2022, 3.5, 0.1750), (2, 'KPI5', '2022-12-31', 2022, 2.5, 0.1250),
(2, 'KPI6', '2022-12-31', 2022, 2.3, 0.1150), (2, 'KPI7', '2022-12-31', 2022, 2.4, 0.1200),
(2, 'KPI8', '2022-12-31', 2022, 3.6, 0.3600), (2, 'KPI9', '2022-12-31', 2022, 3.5, 0.3500),
(2, 'KPI10', '2022-12-31', 2022, 3.4, 0.1275), (2, 'KPI11', '2022-12-31', 2022, 3.3, 0.1238),
(2, 'KPI12', '2022-12-31', 2022, 3.2, 0.1200), (2, 'KPI13', '2022-12-31', 2022, 3.4, 0.1275),
(2, 'KPI14', '2022-12-31', 2022, 3.5, 0.1750), (2, 'KPI15', '2022-12-31', 2022, 3.3, 0.1650),
(2, 'KPI16', '2022-12-31', 2022, 3.6, 0.1200), (2, 'KPI17', '2022-12-31', 2022, 3.4, 0.1133),
(2, 'KPI18', '2022-12-31', 2022, 3.5, 0.1167);

-- SA003 - Carol Martinez (Weak in Sales - needs Sales Excellence training)
INSERT INTO kpi_scores (staff_id, kpi_code, evaluation_date, evaluation_year, score, weighted_score) VALUES
(3, 'CC1', '2022-12-31', 2022, 3.8, 0.1266), (3, 'CC2', '2022-12-31', 2022, 4.0, 0.1332),
(3, 'CC3', '2022-12-31', 2022, 3.9, 0.1303), (3, 'KPI1', '2022-12-31', 2022, 2.8, 0.1400),
(3, 'KPI2', '2022-12-31', 2022, 2.6, 0.1300), (3, 'KPI3', '2022-12-31', 2022, 2.7, 0.1350),
(3, 'KPI4', '2022-12-31', 2022, 2.5, 0.1250), (3, 'KPI5', '2022-12-31', 2022, 4.2, 0.2100),
(3, 'KPI6', '2022-12-31', 2022, 4.1, 0.2050), (3, 'KPI7', '2022-12-31', 2022, 4.0, 0.2000),
(3, 'KPI8', '2022-12-31', 2022, 2.9, 0.2900), (3, 'KPI9', '2022-12-31', 2022, 2.8, 0.2800),
(3, 'KPI10', '2022-12-31', 2022, 3.7, 0.1388), (3, 'KPI11', '2022-12-31', 2022, 3.8, 0.1425),
(3, 'KPI12', '2022-12-31', 2022, 3.6, 0.1350), (3, 'KPI13', '2022-12-31', 2022, 3.7, 0.1388),
(3, 'KPI14', '2022-12-31', 2022, 3.9, 0.1950), (3, 'KPI15', '2022-12-31', 2022, 3.8, 0.1900),
(3, 'KPI16', '2022-12-31', 2022, 4.0, 0.1333), (3, 'KPI17', '2022-12-31', 2022, 3.9, 0.1300),
(3, 'KPI18', '2022-12-31', 2022, 3.8, 0.1267);

-- SA004 - David Lee (Weak in Operations - needs Operational Excellence training)
INSERT INTO kpi_scores (staff_id, kpi_code, evaluation_date, evaluation_year, score, weighted_score) VALUES
(4, 'CC1', '2022-12-31', 2022, 4.0, 0.1332), (4, 'CC2', '2022-12-31', 2022, 3.9, 0.1299),
(4, 'CC3', '2022-12-31', 2022, 4.1, 0.1370), (4, 'KPI1', '2022-12-31', 2022, 4.2, 0.2100),
(4, 'KPI2', '2022-12-31', 2022, 4.0, 0.2000), (4, 'KPI3', '2022-12-31', 2022, 4.1, 0.2050),
(4, 'KPI4', '2022-12-31', 2022, 3.9, 0.1950), (4, 'KPI5', '2022-12-31', 2022, 4.0, 0.2000),
(4, 'KPI6', '2022-12-31', 2022, 3.8, 0.1900), (4, 'KPI7', '2022-12-31', 2022, 3.9, 0.1950),
(4, 'KPI8', '2022-12-31', 2022, 4.1, 0.4100), (4, 'KPI9', '2022-12-31', 2022, 4.0, 0.4000),
(4, 'KPI10', '2022-12-31', 2022, 3.8, 0.1425), (4, 'KPI11', '2022-12-31', 2022, 3.7, 0.1388),
(4, 'KPI12', '2022-12-31', 2022, 3.9, 0.1463), (4, 'KPI13', '2022-12-31', 2022, 3.8, 0.1425),
(4, 'KPI14', '2022-12-31', 2022, 2.6, 0.1300), (4, 'KPI15', '2022-12-31', 2022, 2.5, 0.1250),
(4, 'KPI16', '2022-12-31', 2022, 2.8, 0.0933), (4, 'KPI17', '2022-12-31', 2022, 2.7, 0.0900),
(4, 'KPI18', '2022-12-31', 2022, 2.6, 0.0867);

-- SA005 - Emma Wilson (Inconsistent - needs Time Management training)
INSERT INTO kpi_scores (staff_id, kpi_code, evaluation_date, evaluation_year, score, weighted_score) VALUES
(5, 'CC1', '2022-12-31', 2022, 3.6, 0.1199), (5, 'CC2', '2022-12-31', 2022, 3.5, 0.1166),
(5, 'CC3', '2022-12-31', 2022, 3.7, 0.1236), (5, 'KPI1', '2022-12-31', 2022, 3.4, 0.1700),
(5, 'KPI2', '2022-12-31', 2022, 3.3, 0.1650), (5, 'KPI3', '2022-12-31', 2022, 3.5, 0.1750),
(5, 'KPI4', '2022-12-31', 2022, 3.2, 0.1600), (5, 'KPI5', '2022-12-31', 2022, 3.6, 0.1800),
(5, 'KPI6', '2022-12-31', 2022, 3.4, 0.1700), (5, 'KPI7', '2022-12-31', 2022, 3.5, 0.1750),
(5, 'KPI8', '2022-12-31', 2022, 3.3, 0.3300), (5, 'KPI9', '2022-12-31', 2022, 3.4, 0.3400),
(5, 'KPI10', '2022-12-31', 2022, 3.2, 0.1200), (5, 'KPI11', '2022-12-31', 2022, 3.1, 0.1163),
(5, 'KPI12', '2022-12-31', 2022, 3.3, 0.1238), (5, 'KPI13', '2022-12-31', 2022, 3.2, 0.1200),
(5, 'KPI14', '2022-12-31', 2022, 3.4, 0.1700), (5, 'KPI15', '2022-12-31', 2022, 3.3, 0.1650),
(5, 'KPI16', '2022-12-31', 2022, 2.5, 0.0833), (5, 'KPI17', '2022-12-31', 2022, 2.6, 0.0867),
(5, 'KPI18', '2022-12-31', 2022, 2.4, 0.0800);

-- SA006 - Frank Chen (Good all-around, slight weakness in Competency - needs Product Knowledge training)
INSERT INTO kpi_scores (staff_id, kpi_code, evaluation_date, evaluation_year, score, weighted_score) VALUES
(6, 'CC1', '2022-12-31', 2022, 2.8, 0.0932), (6, 'CC2', '2022-12-31', 2022, 2.6, 0.0866),
(6, 'CC3', '2022-12-31', 2022, 2.7, 0.0903), (6, 'KPI1', '2022-12-31', 2022, 4.0, 0.2000),
(6, 'KPI2', '2022-12-31', 2022, 3.9, 0.1950), (6, 'KPI3', '2022-12-31', 2022, 4.1, 0.2050),
(6, 'KPI4', '2022-12-31', 2022, 3.8, 0.1900), (6, 'KPI5', '2022-12-31', 2022, 4.0, 0.2000),
(6, 'KPI6', '2022-12-31', 2022, 3.9, 0.1950), (6, 'KPI7', '2022-12-31', 2022, 4.0, 0.2000),
(6, 'KPI8', '2022-12-31', 2022, 4.1, 0.4100), (6, 'KPI9', '2022-12-31', 2022, 4.0, 0.4000),
(6, 'KPI10', '2022-12-31', 2022, 3.9, 0.1463), (6, 'KPI11', '2022-12-31', 2022, 3.8, 0.1425),
(6, 'KPI12', '2022-12-31', 2022, 4.0, 0.1500), (6, 'KPI13', '2022-12-31', 2022, 3.9, 0.1463),
(6, 'KPI14', '2022-12-31', 2022, 4.1, 0.2050), (6, 'KPI15', '2022-12-31', 2022, 4.0, 0.2000),
(6, 'KPI16', '2022-12-31', 2022, 3.8, 0.1267), (6, 'KPI17', '2022-12-31', 2022, 3.9, 0.1300),
(6, 'KPI18', '2022-12-31', 2022, 4.0, 0.1333);

-- SA007 - Grace Taylor (Weak in Sales Target - needs Sales Excellence training)
INSERT INTO kpi_scores (staff_id, kpi_code, evaluation_date, evaluation_year, score, weighted_score) VALUES
(7, 'CC1', '2022-12-31', 2022, 3.9, 0.1299), (7, 'CC2', '2022-12-31', 2022, 4.0, 0.1332),
(7, 'CC3', '2022-12-31', 2022, 3.8, 0.1270), (7, 'KPI1', '2022-12-31', 2022, 2.9, 0.1450),
(7, 'KPI2', '2022-12-31', 2022, 2.7, 0.1350), (7, 'KPI3', '2022-12-31', 2022, 2.8, 0.1400),
(7, 'KPI4', '2022-12-31', 2022, 2.6, 0.1300), (7, 'KPI5', '2022-12-31', 2022, 3.8, 0.1900),
(7, 'KPI6', '2022-12-31', 2022, 3.9, 0.1950), (7, 'KPI7', '2022-12-31', 2022, 3.7, 0.1850),
(7, 'KPI8', '2022-12-31', 2022, 3.9, 0.3900), (7, 'KPI9', '2022-12-31', 2022, 4.0, 0.4000),
(7, 'KPI10', '2022-12-31', 2022, 3.8, 0.1425), (7, 'KPI11', '2022-12-31', 2022, 3.9, 0.1463),
(7, 'KPI12', '2022-12-31', 2022, 3.7, 0.1388), (7, 'KPI13', '2022-12-31', 2022, 3.8, 0.1425),
(7, 'KPI14', '2022-12-31', 2022, 4.0, 0.2000), (7, 'KPI15', '2022-12-31', 2022, 3.9, 0.1950),
(7, 'KPI16', '2022-12-31', 2022, 3.8, 0.1267), (7, 'KPI17', '2022-12-31', 2022, 3.9, 0.1300),
(7, 'KPI18', '2022-12-31', 2022, 4.0, 0.1333);

-- SA008 - Henry Brown (Weak in Inventory & Cost Control - needs Operational Excellence)
INSERT INTO kpi_scores (staff_id, kpi_code, evaluation_date, evaluation_year, score, weighted_score) VALUES
(8, 'CC1', '2022-12-31', 2022, 4.1, 0.1366), (8, 'CC2', '2022-12-31', 2022, 4.0, 0.1332),
(8, 'CC3', '2022-12-31', 2022, 4.2, 0.1403), (8, 'KPI1', '2022-12-31', 2022, 3.9, 0.1950),
(8, 'KPI2', '2022-12-31', 2022, 4.0, 0.2000), (8, 'KPI3', '2022-12-31', 2022, 3.8, 0.1900),
(8, 'KPI4', '2022-12-31', 2022, 4.1, 0.2050), (8, 'KPI5', '2022-12-31', 2022, 4.0, 0.2000),
(8, 'KPI6', '2022-12-31', 2022, 3.9, 0.1950), (8, 'KPI7', '2022-12-31', 2022, 4.0, 0.2000),
(8, 'KPI8', '2022-12-31', 2022, 4.1, 0.4100), (8, 'KPI9', '2022-12-31', 2022, 4.0, 0.4000),
(8, 'KPI10', '2022-12-31', 2022, 4.0, 0.1500), (8, 'KPI11', '2022-12-31', 2022, 3.9, 0.1463),
(8, 'KPI12', '2022-12-31', 2022, 4.1, 0.1538), (8, 'KPI13', '2022-12-31', 2022, 4.0, 0.1500),
(8, 'KPI14', '2022-12-31', 2022, 2.7, 0.1350), (8, 'KPI15', '2022-12-31', 2022, 2.6, 0.1300),
(8, 'KPI16', '2022-12-31', 2022, 2.8, 0.0933), (8, 'KPI17', '2022-12-31', 2022, 2.5, 0.0833),
(8, 'KPI18', '2022-12-31', 2022, 2.7, 0.0900);

-- SA009 - Iris Wang (Average performer - needs multiple improvements)
INSERT INTO kpi_scores (staff_id, kpi_code, evaluation_date, evaluation_year, score, weighted_score) VALUES
(9, 'CC1', '2022-12-31', 2022, 3.5, 0.1166), (9, 'CC2', '2022-12-31', 2022, 3.4, 0.1132),
(9, 'CC3', '2022-12-31', 2022, 3.6, 0.1203), (9, 'KPI1', '2022-12-31', 2022, 3.3, 0.1650),
(9, 'KPI2', '2022-12-31', 2022, 3.4, 0.1700), (9, 'KPI3', '2022-12-31', 2022, 3.2, 0.1600),
(9, 'KPI4', '2022-12-31', 2022, 3.5, 0.1750), (9, 'KPI5', '2022-12-31', 2022, 3.3, 0.1650),
(9, 'KPI6', '2022-12-31', 2022, 3.4, 0.1700), (9, 'KPI7', '2022-12-31', 2022, 3.2, 0.1600),
(9, 'KPI8', '2022-12-31', 2022, 3.5, 0.3500), (9, 'KPI9', '2022-12-31', 2022, 3.4, 0.3400),
(9, 'KPI10', '2022-12-31', 2022, 3.3, 0.1238), (9, 'KPI11', '2022-12-31', 2022, 3.4, 0.1275),
(9, 'KPI12', '2022-12-31', 2022, 3.2, 0.1200), (9, 'KPI13', '2022-12-31', 2022, 3.5, 0.1313),
(9, 'KPI14', '2022-12-31', 2022, 3.3, 0.1650), (9, 'KPI15', '2022-12-31', 2022, 3.4, 0.1700),
(9, 'KPI16', '2022-12-31', 2022, 3.2, 0.1067), (9, 'KPI17', '2022-12-31', 2022, 3.3, 0.1100),
(9, 'KPI18', '2022-12-31', 2022, 3.4, 0.1133);

-- SA010 - Jack Miller (Good performer, ready for Leadership training)
INSERT INTO kpi_scores (staff_id, kpi_code, evaluation_date, evaluation_year, score, weighted_score) VALUES
(10, 'CC1', '2022-12-31', 2022, 4.5, 0.1499), (10, 'CC2', '2022-12-31', 2022, 4.6, 0.1532),
(10, 'CC3', '2022-12-31', 2022, 4.4, 0.1470), (10, 'KPI1', '2022-12-31', 2022, 4.7, 0.2350),
(10, 'KPI2', '2022-12-31', 2022, 4.5, 0.2250), (10, 'KPI3', '2022-12-31', 2022, 4.6, 0.2300),
(10, 'KPI4', '2022-12-31', 2022, 4.4, 0.2200), (10, 'KPI5', '2022-12-31', 2022, 4.5, 0.2250),
(10, 'KPI6', '2022-12-31', 2022, 4.6, 0.2300), (10, 'KPI7', '2022-12-31', 2022, 4.4, 0.2200),
(10, 'KPI8', '2022-12-31', 2022, 4.7, 0.4700), (10, 'KPI9', '2022-12-31', 2022, 4.6, 0.4600),
(10, 'KPI10', '2022-12-31', 2022, 4.5, 0.1688), (10, 'KPI11', '2022-12-31', 2022, 4.4, 0.1650),
(10, 'KPI12', '2022-12-31', 2022, 4.6, 0.1725), (10, 'KPI13', '2022-12-31', 2022, 4.5, 0.1688),
(10, 'KPI14', '2022-12-31', 2022, 4.4, 0.2200), (10, 'KPI15', '2022-12-31', 2022, 4.5, 0.2250),
(10, 'KPI16', '2022-12-31', 2022, 4.3, 0.1433), (10, 'KPI17', '2022-12-31', 2022, 4.4, 0.1467),
(10, 'KPI18', '2022-12-31', 2022, 4.5, 0.1500);

-- SA011 - Karen Davis (Weak in Customer Service - needs Customer Service training)
INSERT INTO kpi_scores (staff_id, kpi_code, evaluation_date, evaluation_year, score, weighted_score) VALUES
(11, 'CC1', '2022-12-31', 2022, 3.7, 0.1232), (11, 'CC2', '2022-12-31', 2022, 3.6, 0.1199),
(11, 'CC3', '2022-12-31', 2022, 3.8, 0.1270), (11, 'KPI1', '2022-12-31', 2022, 3.9, 0.1950),
(11, 'KPI2', '2022-12-31', 2022, 3.8, 0.1900), (11, 'KPI3', '2022-12-31', 2022, 4.0, 0.2000),
(11, 'KPI4', '2022-12-31', 2022, 3.7, 0.1850), (11, 'KPI5', '2022-12-31', 2022, 2.6, 0.1300),
(11, 'KPI6', '2022-12-31', 2022, 2.5, 0.1250), (11, 'KPI7', '2022-12-31', 2022, 2.7, 0.1350),
(11, 'KPI8', '2022-12-31', 2022, 3.8, 0.3800), (11, 'KPI9', '2022-12-31', 2022, 3.9, 0.3900),
(11, 'KPI10', '2022-12-31', 2022, 3.7, 0.1388), (11, 'KPI11', '2022-12-31', 2022, 3.6, 0.1350),
(11, 'KPI12', '2022-12-31', 2022, 3.8, 0.1425), (11, 'KPI13', '2022-12-31', 2022, 3.7, 0.1388),
(11, 'KPI14', '2022-12-31', 2022, 3.9, 0.1950), (11, 'KPI15', '2022-12-31', 2022, 3.8, 0.1900),
(11, 'KPI16', '2022-12-31', 2022, 3.7, 0.1233), (11, 'KPI17', '2022-12-31', 2022, 3.6, 0.1200),
(11, 'KPI18', '2022-12-31', 2022, 3.8, 0.1267);

-- SA012 - Leo Garcia (Weak in Store Operations - needs Time Management & Operational Excellence)
INSERT INTO kpi_scores (staff_id, kpi_code, evaluation_date, evaluation_year, score, weighted_score) VALUES
(12, 'CC1', '2022-12-31', 2022, 3.4, 0.1132), (12, 'CC2', '2022-12-31', 2022, 3.3, 0.1099),
(12, 'CC3', '2022-12-31', 2022, 3.5, 0.1170), (12, 'KPI1', '2022-12-31', 2022, 3.6, 0.1800),
(12, 'KPI2', '2022-12-31', 2022, 3.5, 0.1750), (12, 'KPI3', '2022-12-31', 2022, 3.7, 0.1850),
(12, 'KPI4', '2022-12-31', 2022, 3.4, 0.1700), (12, 'KPI5', '2022-12-31', 2022, 3.6, 0.1800),
(12, 'KPI6', '2022-12-31', 2022, 3.5, 0.1750), (12, 'KPI7', '2022-12-31', 2022, 3.4, 0.1700),
(12, 'KPI8', '2022-12-31', 2022, 3.7, 0.3700), (12, 'KPI9', '2022-12-31', 2022, 3.6, 0.3600),
(12, 'KPI10', '2022-12-31', 2022, 2.8, 0.1050), (12, 'KPI11', '2022-12-31', 2022, 2.7, 0.1013),
(12, 'KPI12', '2022-12-31', 2022, 2.9, 0.1088), (12, 'KPI13', '2022-12-31', 2022, 2.6, 0.0975),
(12, 'KPI14', '2022-12-31', 2022, 2.8, 0.1400), (12, 'KPI15', '2022-12-31', 2022, 2.7, 0.1350),
(12, 'KPI16', '2022-12-31', 2022, 2.5, 0.0833), (12, 'KPI17', '2022-12-31', 2022, 2.6, 0.0867),
(12, 'KPI18', '2022-12-31', 2022, 2.4, 0.0800);

-- SA013 - Maria Rodriguez (Balanced performer with minor gaps)
INSERT INTO kpi_scores (staff_id, kpi_code, evaluation_date, evaluation_year, score, weighted_score) VALUES
(13, 'CC1', '2022-12-31', 2022, 4.0, 0.1332), (13, 'CC2', '2022-12-31', 2022, 3.9, 0.1299),
(13, 'CC3', '2022-12-31', 2022, 4.1, 0.1370), (13, 'KPI1', '2022-12-31', 2022, 3.8, 0.1900),
(13, 'KPI2', '2022-12-31', 2022, 3.9, 0.1950), (13, 'KPI3', '2022-12-31', 2022, 3.7, 0.1850),
(13, 'KPI4', '2022-12-31', 2022, 4.0, 0.2000), (13, 'KPI5', '2022-12-31', 2022, 3.8, 0.1900),
(13, 'KPI6', '2022-12-31', 2022, 3.9, 0.1950), (13, 'KPI7', '2022-12-31', 2022, 3.7, 0.1850),
(13, 'KPI8', '2022-12-31', 2022, 4.0, 0.4000), (13, 'KPI9', '2022-12-31', 2022, 3.9, 0.3900),
(13, 'KPI10', '2022-12-31', 2022, 3.8, 0.1425), (13, 'KPI11', '2022-12-31', 2022, 3.7, 0.1388),
(13, 'KPI12', '2022-12-31', 2022, 3.9, 0.1463), (13, 'KPI13', '2022-12-31', 2022, 3.8, 0.1425),
(13, 'KPI14', '2022-12-31', 2022, 4.0, 0.2000), (13, 'KPI15', '2022-12-31', 2022, 3.9, 0.1950),
(13, 'KPI16', '2022-12-31', 2022, 3.7, 0.1233), (13, 'KPI17', '2022-12-31', 2022, 3.8, 0.1267),
(13, 'KPI18', '2022-12-31', 2022, 3.9, 0.1300);

-- ============================================================================
-- STAFF COMMENTS FOR 2022
-- ============================================================================
INSERT INTO staff_comments (staff_id, supervisor_id, comment_date, comment_year, comment_text) VALUES
(1, 1, '2022-12-31', 2022, 'Exceptional performance across all KPIs. Alice consistently exceeds targets and demonstrates strong leadership potential. Ready for advanced training.'),
(2, 1, '2022-12-31', 2022, 'Good sales performance but needs significant improvement in customer service skills. Recommend Customer Service Mastery training program.'),
(3, 1, '2022-12-31', 2022, 'Excellent customer service but struggling with sales targets. Needs focused sales training to improve conversion rates and target achievement.'),
(4, 2, '2022-12-31', 2022, 'Strong in sales and customer service but operational efficiency needs improvement. Recommend Operational Excellence training.'),
(5, 2, '2022-12-31', 2022, 'Inconsistent performance with punctuality issues affecting overall productivity. Time Management training would be beneficial.'),
(6, 2, '2022-12-31', 2022, 'Good all-around performer but lacks product knowledge depth. Advanced Product Knowledge training recommended.'),
(7, 3, '2022-12-31', 2022, 'Strong in customer service and operations but sales targets consistently missed. Sales Excellence Program needed.'),
(8, 3, '2022-12-31', 2022, 'Excellent in most areas but inventory management and cost control need attention. Operational Excellence training recommended.'),
(9, 3, '2022-12-31', 2022, 'Average performer across all categories. Needs comprehensive skill development in multiple areas.'),
(10, 1, '2022-12-31', 2022, 'Outstanding performer with consistent high scores. Demonstrates leadership qualities. Ready for Leadership Fundamentals program.'),
(11, 2, '2022-12-31', 2022, 'Good sales performance but customer service skills need development. Customer Service Mastery training recommended.'),
(12, 2, '2022-12-31', 2022, 'Decent sales but poor operational support and time management. Needs Time Management and Operational Excellence training.'),
(13, 3, '2022-12-31', 2022, 'Well-balanced performer with minor gaps. Continued development in all areas will lead to excellence.');

-- ============================================================================
-- Note: For years 2023-2025, use the following SQL to generate progressive data:
-- Run this after importing 2022 data to see improvements over time
-- ============================================================================
