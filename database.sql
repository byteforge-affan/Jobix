-- HireHub Database Schema
-- Version 1.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `hirehub` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hirehub`;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','company','candidate') NOT NULL DEFAULT 'candidate',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: companies
-- --------------------------------------------------------
CREATE TABLE `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `founded_year` year DEFAULT NULL,
  `company_size` enum('1-10','11-50','51-200','201-500','501-1000','1000+') DEFAULT '1-10',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `companies_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: candidates
-- --------------------------------------------------------
CREATE TABLE `candidates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `headline` varchar(200) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `education` text DEFAULT NULL,
  `experience` text DEFAULT NULL,
  `resume` varchar(255) DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `portfolio` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `candidates_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: job_categories
-- --------------------------------------------------------
CREATE TABLE `job_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT 'briefcase',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: jobs
-- --------------------------------------------------------
CREATE TABLE `jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `type` enum('full-time','part-time','remote','internship','contract') NOT NULL DEFAULT 'full-time',
  `salary_min` decimal(10,2) DEFAULT NULL,
  `salary_max` decimal(10,2) DEFAULT NULL,
  `salary_currency` varchar(10) DEFAULT 'USD',
  `location` varchar(150) DEFAULT NULL,
  `description` longtext NOT NULL,
  `requirements` text DEFAULT NULL,
  `benefits` text DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `views` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `jobs_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `jobs_category_fk` FOREIGN KEY (`category_id`) REFERENCES `job_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: applications
-- --------------------------------------------------------
CREATE TABLE `applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `resume` varchar(255) DEFAULT NULL,
  `status` enum('pending','reviewed','shortlisted','rejected','hired') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_application` (`job_id`,`candidate_id`),
  KEY `job_id` (`job_id`),
  KEY `candidate_id` (`candidate_id`),
  CONSTRAINT `applications_job_fk` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `applications_candidate_fk` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: saved_jobs
-- --------------------------------------------------------
CREATE TABLE `saved_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `candidate_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `saved_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_saved` (`candidate_id`,`job_id`),
  CONSTRAINT `saved_candidate_fk` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saved_job_fk` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: notifications
-- --------------------------------------------------------
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notif_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- DUMMY DATA
-- ========================================

-- Admin user (password: admin123)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `is_active`) VALUES
('Super Admin', 'admin@hirehub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);

-- Company users (password: password)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `is_active`) VALUES
('TechNova Solutions', 'hr@technova.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'company', 1),
('ByteForge Labs', 'jobs@byteforge.io', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'company', 1),
('FutureSoft Inc', 'careers@futuresoft.co', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'company', 1);

-- Candidate users (password: password)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `is_active`) VALUES
('Ahmed Khan', 'ahmed@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'candidate', 1),
('Sarah Ali', 'sarah@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'candidate', 1),
('John Smith', 'john@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'candidate', 1);

-- Companies
INSERT INTO `companies` (`user_id`, `company_name`, `industry`, `website`, `description`, `location`, `phone`, `founded_year`, `company_size`, `is_verified`) VALUES
(2, 'TechNova Solutions', 'Information Technology', 'https://technova.com', 'TechNova Solutions is a leading software development company specializing in enterprise solutions, cloud infrastructure, and digital transformation. We build cutting-edge products that power businesses globally.', 'Karachi, Pakistan', '+92-21-111-000-001', 2015, '201-500', 1),
(3, 'ByteForge Labs', 'Software Development', 'https://byteforge.io', 'ByteForge Labs is a product-first startup studio building innovative SaaS tools for modern teams. We move fast, iterate faster, and ship products users love.', 'Lahore, Pakistan', '+92-42-111-000-002', 2019, '11-50', 1),
(4, 'FutureSoft Inc', 'Technology Consulting', 'https://futuresoft.co', 'FutureSoft Inc delivers world-class consulting and software engineering services to Fortune 500 companies and fast-growing startups alike. Our team of 1000+ engineers drive digital innovation globally.', 'Islamabad, Pakistan', '+92-51-111-000-003', 2010, '1000+', 1);

-- Candidates
INSERT INTO `candidates` (`user_id`, `headline`, `skills`, `education`, `experience`, `location`, `phone`, `linkedin`) VALUES
(5, 'Senior PHP Developer | 5+ Years Experience', 'PHP, Laravel, MySQL, JavaScript, Vue.js, Docker, Git, REST APIs', 'BS Computer Science – FAST NUCES (2015–2019)', '5 years at Arbisoft as PHP Developer; 2 years freelance on Upwork', 'Karachi, Pakistan', '+92-300-1234567', 'https://linkedin.com/in/ahmedkhan'),
(6, 'UI/UX Designer & Frontend Developer', 'Figma, Adobe XD, HTML5, CSS3, React.js, Tailwind CSS, Bootstrap', 'BS Software Engineering – UET Lahore (2016–2020)', '3 years at Xord as UI/UX Designer; 1 year at Creative Chaos', 'Lahore, Pakistan', '+92-312-9876543', 'https://linkedin.com/in/sarahali'),
(7, 'Full Stack Developer | React & Node.js', 'JavaScript, React, Node.js, MongoDB, Express.js, GraphQL, AWS', 'MS Computer Science – University of Toronto (2018–2020)', '4 years at Amazon as SDE; 1 year at a fintech startup', 'Islamabad, Pakistan', '+92-321-4567890', 'https://linkedin.com/in/johnsmith');

-- Job Categories
INSERT INTO `job_categories` (`name`, `icon`) VALUES
('Software Development', 'code-slash'),
('Design & Creative', 'palette'),
('Marketing', 'megaphone'),
('Data Science', 'graph-up'),
('DevOps & Cloud', 'cloud'),
('Project Management', 'kanban'),
('QA & Testing', 'bug'),
('Mobile Development', 'phone');

-- Jobs
INSERT INTO `jobs` (`company_id`, `category_id`, `title`, `type`, `salary_min`, `salary_max`, `salary_currency`, `location`, `description`, `requirements`, `benefits`, `deadline`, `is_active`, `views`) VALUES
(1, 1, 'Senior PHP Developer', 'full-time', 120000, 180000, 'PKR', 'Karachi, Pakistan', '<p>We are looking for an experienced PHP Developer to join our backend team at TechNova Solutions. You will be responsible for building and maintaining scalable web applications using PHP and Laravel.</p><p>This is an exciting opportunity to work with a talented team on enterprise-grade products serving thousands of users daily.</p>', '• 4+ years PHP experience\n• Strong Laravel framework knowledge\n• MySQL / PostgreSQL expertise\n• Experience with REST APIs\n• Git version control\n• Understanding of MVC architecture', '• Competitive salary\n• Health insurance\n• Annual bonus\n• Remote work options\n• Learning & development budget', '2025-03-31', 1, 245),
(1, 1, 'Laravel Developer', 'full-time', 100000, 150000, 'PKR', 'Karachi, Pakistan', '<p>TechNova Solutions is hiring a Laravel Developer to work on our flagship SaaS product. You will collaborate closely with our product and design teams to build new features and improve existing ones.</p>', '• 2+ years Laravel experience\n• PHP 8 proficiency\n• Blade templating\n• Eloquent ORM\n• Queue & job management\n• Redis experience is a plus', '• Medical coverage\n• Flexible hours\n• Team retreats\n• Stock options\n• Modern equipment', '2025-04-15', 1, 189),
(2, 2, 'UI/UX Designer', 'full-time', 80000, 120000, 'PKR', 'Lahore, Pakistan', '<p>ByteForge Labs is looking for a passionate UI/UX Designer who loves crafting beautiful and intuitive digital experiences. You will own the design process from wireframes to final handoff.</p>', '• 3+ years UI/UX experience\n• Proficiency in Figma\n• Strong portfolio required\n• Understanding of user research\n• Ability to prototype\n• Design system experience', '• Creative environment\n• Equity participation\n• Unlimited PTO\n• Annual retreats\n• Home office budget', '2025-04-30', 1, 312),
(2, 1, 'Frontend Developer', 'remote', 90000, 140000, 'PKR', 'Remote', '<p>Join ByteForge Labs as a Frontend Developer and help us build next-generation SaaS products. This is a fully remote role with flexible working hours.</p>', '• 2+ years React.js experience\n• TypeScript proficiency\n• REST API integration\n• CSS / Tailwind CSS\n• Git workflow\n• Testing knowledge', '• 100% remote\n• Flexible schedule\n• Home office stipend\n• Health benefits\n• Career growth', '2025-05-15', 1, 421),
(3, 1, 'Software Engineer', 'full-time', 150000, 250000, 'PKR', 'Islamabad, Pakistan', '<p>FutureSoft Inc is seeking a talented Software Engineer to join our engineering team in Islamabad. You will work on complex distributed systems and cutting-edge technology challenges.</p>', '• 3+ years software engineering\n• Java or Python expertise\n• Microservices architecture\n• AWS / Azure cloud platforms\n• System design skills\n• Agile methodology', '• Top-tier salary\n• Comprehensive health plan\n• International exposure\n• 20 days annual leave\n• Professional certifications', '2025-05-31', 1, 567),
(3, 8, 'Mobile Developer (React Native)', 'full-time', 100000, 160000, 'PKR', 'Islamabad, Pakistan', '<p>We are building the next generation of mobile applications and need an experienced React Native developer to lead our mobile team at FutureSoft Inc.</p>', '• 2+ years React Native\n• iOS & Android deployment\n• Redux or Context API\n• REST API integration\n• App Store & Play Store experience\n• Performance optimization', '• Hybrid work model\n• Phone allowance\n• Training budget\n• Performance bonus\n• Team lunch Fridays', '2025-06-30', 1, 134);

-- Applications
INSERT INTO `applications` (`job_id`, `candidate_id`, `cover_letter`, `status`) VALUES
(1, 1, 'I am very excited to apply for the Senior PHP Developer position at TechNova Solutions. With 5 years of PHP and Laravel experience, I am confident I can contribute significantly to your backend team and help build scalable enterprise solutions.', 'shortlisted'),
(2, 1, 'Having worked extensively with Laravel for the past 3 years, I believe I am an excellent fit for the Laravel Developer role. I have built multiple production SaaS applications and am passionate about clean code and best practices.', 'reviewed'),
(3, 2, 'As a UI/UX Designer with 3 years of experience at top tech companies, I am thrilled to apply for this opportunity at ByteForge Labs. My portfolio demonstrates my ability to create beautiful, user-centered designs.', 'pending'),
(4, 2, 'This frontend role aligns perfectly with my skills in React.js and TypeScript. I have previously built fully responsive SPAs for enterprise clients and am excited about the possibility of joining ByteForge Labs remote team.', 'hired'),
(5, 3, 'I bring 4 years of software engineering experience from Amazon, where I worked on large-scale distributed systems. I am now looking for opportunities in Pakistan and believe FutureSoft would be an amazing fit.', 'shortlisted'),
(1, 2, 'While my primary background is design, I also have solid PHP knowledge from working closely with developers. I would love to explore this full-stack opportunity at TechNova.', 'rejected'),
(5, 1, 'I am applying for the Software Engineer role with enthusiasm. My diverse background in PHP and system design gives me a unique perspective for this role.', 'pending'),
(3, 3, 'As someone who has worked at Amazon and built interfaces for millions of users, I understand deeply what makes great user experience. Excited to apply at ByteForge.', 'reviewed');

-- Saved Jobs
INSERT INTO `saved_jobs` (`candidate_id`, `job_id`) VALUES
(1, 3), (1, 4), (1, 5),
(2, 1), (2, 5), (2, 6),
(3, 1), (3, 2), (3, 4);

-- Notifications
INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `link`, `is_read`) VALUES
(5, 'application_status', 'Application Shortlisted!', 'Your application for Senior PHP Developer at TechNova Solutions has been shortlisted.', '/candidate/applications.php', 0),
(5, 'application_status', 'Application Reviewed', 'Your application for Laravel Developer at TechNova Solutions has been reviewed.', '/candidate/applications.php', 0),
(2, 'new_application', 'New Application Received', 'Ahmed Khan applied for Senior PHP Developer position.', '/company/applications.php', 1),
(6, 'application_status', 'Congratulations! You are Hired!', 'Your application for Frontend Developer at ByteForge Labs has been accepted. Welcome aboard!', '/candidate/applications.php', 0),
(3, 'new_application', 'New Application Received', 'Sarah Ali applied for UI/UX Designer position.', '/company/applications.php', 0);

COMMIT;
