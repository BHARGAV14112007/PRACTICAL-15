-- ============================================================================
-- StudentHub - Practical 11: student seed data (16 additional records)
-- ----------------------------------------------------------------------------
-- Idempotent: INSERT IGNORE + UNIQUE(email) means re-running this file NEVER
-- creates duplicates. All sample passwords are 'Secret123' (bcrypt hash).
-- After this seed: 5 (Practical 8) + 16 = 21 student rows (+ 1 admin row).
-- Apply in phpMyAdmin: select `studenthub` > Import > this file > Import.
-- ============================================================================

USE `studenthub`;

INSERT IGNORE INTO `students`
    (`full_name`, `email`, `mobile`, `course`, `academic_year`,
     `gender`, `password_hash`, `department`, `semester`)
VALUES
    ('Arjun Nair',     'arjun.nair@college.edu',     '9890123456', 'B.Tech', 'First Year',  'Male',   '$2y$10$1Q/aMn3m1dJhGtGGjJTsVOJ7cJakGBH281xV2iEIB44FazCp8RKPC', 'Computer Science', 1),
    ('Divya Menon',    'divya.menon@college.edu',    '9891234567', 'B.Tech', 'Second Year', 'Female', '$2y$10$3D1X5KLV7f8Sbld0.xZEFuzGfhOgnwA7FPJfa8kqN1zwQbTjWAvve', 'Information Technology', 3),
    ('Kabir Singh',    'kabir.singh@college.edu',    '9812345690', 'BBA',    'First Year',  'Male',   '$2y$10$h2R1AGbC2NaemZeWskL7keZvHHmnCKsBvh96hynIm03IwYxikSv76', 'Business Administration', 1),
    ('Meera Iyer',     'meera.iyer@college.edu',     '9765432109', 'BCA',    'First Year',  'Female', '$2y$10$1Q/aMn3m1dJhGtGGjJTsVOJ7cJakGBH281xV2iEIB44FazCp8RKPC', 'Computer Applications', 1),
    ('Aditi Rao',      'aditi.rao@college.edu',      '9654321098', 'B.Tech', 'Final Year',  'Female', '$2y$10$3D1X5KLV7f8Sbld0.xZEFuzGfhOgnwA7FPJfa8kqN1zwQbTjWAvve', 'Electronics Engineering', 7),
    ('Varun Malhotra', 'varun.malhotra@college.edu', '9988776655', 'B.Tech', 'Third Year',  'Male',   '$2y$10$h2R1AGbC2NaemZeWskL7keZvHHmnCKsBvh96hynIm03IwYxikSv76', 'Mechanical Engineering', 5),
    ('Pooja Desai',    'pooja.desai@college.edu',    '9876543201', 'BCA',    'Third Year',  'Female', '$2y$10$1Q/aMn3m1dJhGtGGjJTsVOJ7cJakGBH281xV2iEIB44FazCp8RKPC', 'Computer Applications', 5),
    ('Sameer Khan',    'sameer.khan@college.edu',    '9123456709', 'BBA',    'Second Year', 'Male',   '$2y$10$3D1X5KLV7f8Sbld0.xZEFuzGfhOgnwA7FPJfa8kqN1zwQbTjWAvve', 'Business Administration', 3),
    ('Riya Kapoor',    'riya.kapoor@college.edu',    '9012345678', 'B.Tech', 'Second Year', 'Female', '$2y$10$1Q/aMn3m1dJhGtGGjJTsVOJ7cJakGBH281xV2iEIB44FazCp8RKPC', 'Computer Science', 3),
    ('Harsh Vardhan',  'harsh.vardhan@college.edu',  '8899001122', 'B.Tech', 'First Year',  'Male',   '$2y$10$3D1X5KLV7f8Sbld0.xZEFuzGfhOgnwA7FPJfa8kqN1zwQbTjWAvve', 'Civil Engineering', 1),
    ('Tanvi Shah',     'tanvi.shah@college.edu',     '7788990011', 'BCA',    'Second Year', 'Female', '$2y$10$h2R1AGbC2NaemZeWskL7keZvHHmnCKsBvh96hynIm03IwYxikSv76', 'Computer Applications', 3),
    ('Nikhil Bose',    'nikhil.bose@college.edu',    '6677889900', 'B.Tech', 'Final Year',  'Male',   '$2y$10$1Q/aMn3m1dJhGtGGjJTsVOJ7cJakGBH281xV2iEIB44FazCp8RKPC', 'Information Technology', 7),
    ('Shreya Kulkarni','shreya.kulkarni@college.edu','9567890123', 'BBA',    'Third Year',  'Female', '$2y$10$3D1X5KLV7f8Sbld0.xZEFuzGfhOgnwA7FPJfa8kqN1zwQbTjWAvve', 'Business Administration', 5),
    ('Farhan Ali',     'farhan.ali@college.edu',     '9456789012', 'B.Tech', 'Third Year',  'Male',   '$2y$10$h2R1AGbC2NaemZeWskL7keZvHHmnCKsBvh96hynIm03IwYxikSv76', 'Computer Science', 5),
    ('Anjali Mishra',  'anjali.mishra@college.edu',  '9345678901', 'BCA',    'Final Year',  'Female', '$2y$10$1Q/aMn3m1dJhGtGGjJTsVOJ7cJakGBH281xV2iEIB44FazCp8RKPC', 'Computer Applications', 7),
    ('Gaurav Thakur',  'gaurav.thakur@college.edu',  '9234567890', 'BBA',    'Final Year',  'Male',   '$2y$10$3D1X5KLV7f8Sbld0.xZEFuzGfhOgnwA7FPJfa8kqN1zwQbTjWAvve', 'Business Administration', 7);
