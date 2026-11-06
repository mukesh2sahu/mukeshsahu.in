-- MukeshSahu Platform Database Schema
-- Version 1.0.0
-- Charset: utf8mb4 / Collation: utf8mb4_unicode_ci

CREATE DATABASE IF NOT EXISTS `mukeshsahu_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mukeshsahu_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Table: user_roles
DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE `user_roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_name` VARCHAR(50) NOT NULL UNIQUE,
    `display_name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table: users
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT UNSIGNED NOT NULL DEFAULT 3,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `mobile` VARCHAR(20) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `status` ENUM('active', 'inactive', 'banned') NOT NULL DEFAULT 'active',
    `is_email_verified` TINYINT(1) NOT NULL DEFAULT 1,
    `remember_token` VARCHAR(100) NULL,
    `failed_login_attempts` INT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until` DATETIME NULL,
    `last_login_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table: user_profiles
DROP TABLE IF EXISTS `user_profiles`;
CREATE TABLE `user_profiles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `headline` VARCHAR(200) NULL,
    `bio` TEXT NULL,
    `avatar` VARCHAR(255) NULL,
    `github_url` VARCHAR(255) NULL,
    `linkedin_url` VARCHAR(255) NULL,
    `portfolio_url` VARCHAR(255) NULL,
    `skills` TEXT NULL,
    `location` VARCHAR(100) NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table: job_categories
DROP TABLE IF EXISTS `job_categories`;
CREATE TABLE `job_categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(120) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `icon` VARCHAR(50) DEFAULT 'fa-briefcase',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Table: jobs
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `company_name` VARCHAR(150) NOT NULL,
    `company_logo` VARCHAR(255) NULL,
    `location` VARCHAR(150) NOT NULL,
    `work_mode` ENUM('Remote', 'Hybrid', 'On-site') NOT NULL DEFAULT 'Remote',
    `experience_level` VARCHAR(80) NOT NULL,
    `salary_range` VARCHAR(100) NOT NULL,
    `employment_type` ENUM('Full-time', 'Part-time', 'Contract', 'Internship') NOT NULL DEFAULT 'Full-time',
    `required_skills` TEXT NOT NULL,
    `description` LONGTEXT NOT NULL,
    `eligibility_criteria` TEXT NULL,
    `application_deadline` DATE NULL,
    `application_link` VARCHAR(500) NOT NULL,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('published', 'draft', 'expired') NOT NULL DEFAULT 'published',
    `posted_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_jobs_category` FOREIGN KEY (`category_id`) REFERENCES `job_categories` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_jobs_posted_by` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_jobs_featured` (`is_featured`),
    INDEX `idx_jobs_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Table: saved_jobs
DROP TABLE IF EXISTS `saved_jobs`;
CREATE TABLE `saved_jobs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `job_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user_job` (`user_id`, `job_id`),
    CONSTRAINT `fk_saved_jobs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_saved_jobs_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Table: course_categories
DROP TABLE IF EXISTS `course_categories`;
CREATE TABLE `course_categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(120) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `icon` VARCHAR(50) DEFAULT 'fa-graduation-cap',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Table: courses
DROP TABLE IF EXISTS `courses`;
CREATE TABLE `courses` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `thumbnail` VARCHAR(255) NULL,
    `description` LONGTEXT NOT NULL,
    `instructor_name` VARCHAR(120) NOT NULL DEFAULT 'Mukesh Sahu',
    `skill_level` ENUM('Beginner', 'Intermediate', 'Advanced', 'All Levels') NOT NULL DEFAULT 'Beginner',
    `duration` VARCHAR(80) NOT NULL,
    `total_lectures` INT UNSIGNED NOT NULL DEFAULT 0,
    `language` VARCHAR(50) NOT NULL DEFAULT 'English & Hindi',
    `playlist_url` VARCHAR(500) NULL,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('published', 'draft', 'archived') NOT NULL DEFAULT 'published',
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_courses_category` FOREIGN KEY (`category_id`) REFERENCES `course_categories` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_courses_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_courses_featured` (`is_featured`),
    INDEX `idx_courses_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Table: course_modules
DROP TABLE IF EXISTS `course_modules`;
CREATE TABLE `course_modules` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `course_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `order_index` INT UNSIGNED NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_modules_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Table: course_lectures
DROP TABLE IF EXISTS `course_lectures`;
CREATE TABLE `course_lectures` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `course_id` INT UNSIGNED NOT NULL,
    `module_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `youtube_video_id` VARCHAR(50) NOT NULL,
    `duration_minutes` INT UNSIGNED NOT NULL DEFAULT 10,
    `order_index` INT UNSIGNED NOT NULL DEFAULT 1,
    `is_free_preview` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_lectures_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_lectures_module` FOREIGN KEY (`module_id`) REFERENCES `course_modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Table: course_enrollments
DROP TABLE IF EXISTS `course_enrollments`;
CREATE TABLE `course_enrollments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `course_id` INT UNSIGNED NOT NULL,
    `progress_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `last_lecture_id` INT UNSIGNED NULL,
    `enrolled_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_accessed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `completed_at` DATETIME NULL,
    UNIQUE KEY `uk_user_course` (`user_id`, `course_id`),
    CONSTRAINT `fk_enrollments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_enrollments_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Table: lecture_progress
DROP TABLE IF EXISTS `lecture_progress`;
CREATE TABLE `lecture_progress` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `course_id` INT UNSIGNED NOT NULL,
    `lecture_id` INT UNSIGNED NOT NULL,
    `is_completed` TINYINT(1) NOT NULL DEFAULT 1,
    `completed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user_lecture` (`user_id`, `lecture_id`),
    CONSTRAINT `fk_progress_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_progress_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_progress_lecture` FOREIGN KEY (`lecture_id`) REFERENCES `course_lectures` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Table: ai_tool_categories
DROP TABLE IF EXISTS `ai_tool_categories`;
CREATE TABLE `ai_tool_categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(120) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `icon` VARCHAR(50) DEFAULT 'fa-robot',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Table: ai_tools
DROP TABLE IF EXISTS `ai_tools`;
CREATE TABLE `ai_tools` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(160) NOT NULL UNIQUE,
    `logo` VARCHAR(255) NULL,
    `description` TEXT NOT NULL,
    `official_url` VARCHAR(500) NOT NULL,
    `pricing_type` ENUM('Free', 'Paid', 'Freemium') NOT NULL DEFAULT 'Freemium',
    `features_json` TEXT NULL,
    `user_rating` DECIMAL(3,2) NOT NULL DEFAULT 4.50,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_aitools_category` FOREIGN KEY (`category_id`) REFERENCES `ai_tool_categories` (`id`) ON DELETE RESTRICT,
    INDEX `idx_aitools_featured` (`is_featured`),
    INDEX `idx_aitools_pricing` (`pricing_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Table: saved_ai_tools
DROP TABLE IF EXISTS `saved_ai_tools`;
CREATE TABLE `saved_ai_tools` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `tool_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user_tool` (`user_id`, `tool_id`),
    CONSTRAINT `fk_saved_tools_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_saved_tools_tool` FOREIGN KEY (`tool_id`) REFERENCES `ai_tools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Table: blog_categories
DROP TABLE IF EXISTS `blog_categories`;
CREATE TABLE `blog_categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(120) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Table: blog_tags
DROP TABLE IF EXISTS `blog_tags`;
CREATE TABLE `blog_tags` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(80) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Table: blogs
DROP TABLE IF EXISTS `blogs`;
CREATE TABLE `blogs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT UNSIGNED NOT NULL,
    `author_id` INT UNSIGNED NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `featured_image` VARCHAR(255) NULL,
    `excerpt` TEXT NULL,
    `content` LONGTEXT NOT NULL,
    `meta_title` VARCHAR(255) NULL,
    `meta_description` VARCHAR(350) NULL,
    `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM('published', 'draft', 'archived') NOT NULL DEFAULT 'published',
    `published_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_blogs_category` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_blogs_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_blogs_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Table: blog_tag_relations
DROP TABLE IF EXISTS `blog_tag_relations`;
CREATE TABLE `blog_tag_relations` (
    `blog_id` INT UNSIGNED NOT NULL,
    `tag_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`blog_id`, `tag_id`),
    CONSTRAINT `fk_btr_blog` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_btr_tag` FOREIGN KEY (`tag_id`) REFERENCES `blog_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Table: contact_messages
DROP TABLE IF EXISTS `contact_messages`;
CREATE TABLE `contact_messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `mobile` VARCHAR(25) NOT NULL,
    `subject` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `reply_notes` TEXT NULL,
    `ip_address` VARCHAR(50) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_contact_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Table: admin_activity_logs
DROP TABLE IF EXISTS `admin_activity_logs`;
CREATE TABLE `admin_activity_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NULL,
    `action` VARCHAR(100) NOT NULL,
    `module` VARCHAR(80) NOT NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(50) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_activity_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Table: website_settings
DROP TABLE IF EXISTS `website_settings`;
CREATE TABLE `website_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` LONGTEXT NULL,
    `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. Table: password_resets
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(150) NOT NULL,
    `token` VARCHAR(100) NOT NULL UNIQUE,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_pwd_resets_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. Table: email_verifications
DROP TABLE IF EXISTS `email_verifications`;
CREATE TABLE `email_verifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `token` VARCHAR(100) NOT NULL UNIQUE,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_email_verif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ========================================================
-- SEED DATA
-- ========================================================

-- Seed User Roles
INSERT INTO `user_roles` (`id`, `role_name`, `display_name`, `description`) VALUES
(1, 'super_admin', 'Super Admin', 'Full control over system, settings, and users'),
(2, 'admin', 'Administrator', 'Can manage content, courses, jobs, tools, and blogs'),
(3, 'user', 'Registered User', 'Standard registered member with access to restricted portals');

-- Seed Users (Passwords: Admin@12345 and User@12345)
INSERT INTO `users` (`id`, `role_id`, `name`, `email`, `mobile`, `password`, `status`, `is_email_verified`) VALUES
(1, 1, 'Mukesh Sahu', 'admin@mukeshsahu.in', '+91 9876543210', '$2y$10$.Yx9vYVGJCQV8Y0j4IhFzuZKeiCJmX.mvvCBsbM7ACveJofKsA.O.', 'active', 1),
(2, 3, 'John Doe', 'user@mukeshsahu.in', '+91 9123456789', '$2y$10$mK9SHEJw./ZQ3F2d0z31x.jWLGM/K/MZyPdo8mZV3j4SlW.iF47Iy', 'active', 1);

-- Seed User Profiles
INSERT INTO `user_profiles` (`user_id`, `headline`, `bio`, `github_url`, `linkedin_url`, `portfolio_url`, `skills`, `location`) VALUES
(1, 'Full Stack Developer & AI Enthusiast', 'Passionate Software Engineer, Mentor, and Content Creator dedicated to empowering learners with modern programming languages, AI engineering, and career advancement.', 'https://github.com/mukeshsahu', 'https://linkedin.com/in/mukeshsahu', 'https://mukeshsahu.in', 'PHP, MySQL, Python, React.js, LangChain, Generative AI, Cloud Architecture', 'Bengaluru, India'),
(2, 'Aspiring Full Stack Engineer', 'Junior developer learning PHP, modern JS, and AI application building.', 'https://github.com/johndoe', 'https://linkedin.com/in/johndoe', '', 'PHP, JavaScript, HTML/CSS', 'Mumbai, India');

-- Seed Job Categories
INSERT INTO `job_categories` (`id`, `name`, `slug`, `description`, `icon`) VALUES
(1, 'Software Developer', 'software-developer', 'General software development roles across various languages', 'fa-laptop-code'),
(2, 'PHP Developer', 'php-developer', 'Core PHP, Laravel, Symfony, and Full-Stack LAMP stack roles', 'fa-code'),
(3, 'Python Developer', 'python-developer', 'Python backend, Django, FastAPI, scripting, and automation', 'fa-terminal'),
(4, 'Java Developer', 'java-developer', 'Enterprise Java, Spring Boot, Microservices, and cloud systems', 'fa-cubes'),
(5, 'React Developer', 'react-developer', 'Frontend engineering with React.js, Next.js, and Redux', 'fa-atom'),
(6, 'Data Analyst', 'data-analyst', 'SQL, Excel, Power BI, Tableau, and business analytics', 'fa-chart-pie'),
(7, 'Data Scientist', 'data-scientist', 'Machine learning algorithms, Python, statistical modeling', 'fa-brain'),
(8, 'AI/ML Engineer', 'ai-ml-engineer', 'Deep learning, LLMs, LangChain, RAG pipelines, PyTorch', 'fa-microchip'),
(9, 'Full Stack Developer', 'full-stack-developer', 'End-to-end web application development and architecture', 'fa-layer-group'),
(10, 'DevOps Engineer', 'devops-engineer', 'CI/CD, Docker, Kubernetes, AWS, Terraform, Cloud operations', 'fa-server'),
(11, 'Internship', 'internship', 'Graduate and student internships in tech and development', 'fa-graduation-cap');

-- Seed Sample Jobs
INSERT INTO `jobs` (`id`, `category_id`, `title`, `company_name`, `company_logo`, `location`, `work_mode`, `experience_level`, `salary_range`, `employment_type`, `required_skills`, `description`, `eligibility_criteria`, `application_deadline`, `application_link`, `is_featured`, `status`, `posted_by`) VALUES
(1, 2, 'Senior PHP / Laravel Engineer', 'TechNova Solutions', 'technova.png', 'Bengaluru / Remote', 'Hybrid', '3 - 6 Years', '12,00,000 - 18,00,000 P.A.', 'Full-time', 'PHP 8+, Laravel, MySQL, Redis, REST APIs, Docker', 'We are looking for a Senior PHP Developer with extensive experience in architecting scalable web applications, designing RESTful APIs, and optimizing database queries.', 'B.Tech/MCA/BCA in Computer Science or equivalent experience with solid portfolio.', '2027-03-31', 'https://example.com/apply/php-senior', 1, 'published', 1),
(2, 8, 'Generative AI & LLM Engineer', 'Cognitive Cloud Labs', 'cognitive.png', 'Hyderabad / Remote', 'Remote', '2 - 5 Years', '18,00,000 - 28,00,000 P.A.', 'Full-time', 'Python, LangChain, OpenAI APIs, Vector DBs, RAG, FastAPI', 'Join our cutting-edge AI team to build autonomous agents, retrieval-augmented generation systems, and enterprise LLM solutions.', 'Demonstrated experience fine-tuning LLMs or building production LangChain/LlamaIndex apps.', '2027-04-15', 'https://example.com/apply/ai-engineer', 1, 'published', 1),
(3, 5, 'Frontend Developer (React.js / Next.js)', 'PixelCraft Media', 'pixelcraft.png', 'Pune / Mumbai', 'Remote', '1 - 3 Years', '7,00,000 - 12,00,000 P.A.', 'Full-time', 'React.js, Next.js, TypeScript, Tailwind CSS, Redux Toolkit', 'Build responsive, lightning-fast web applications with modern React hooks, server components, and clean UI/UX standards.', 'Strong proficiency in modern JavaScript (ES6+), responsive design, and state management.', '2027-04-30', 'https://example.com/apply/react-dev', 1, 'published', 1),
(4, 3, 'Python Backend & Automation Specialist', 'DataFlow Systems', 'dataflow.png', 'Gurugram / Noida', 'Hybrid', '2 - 4 Years', '9,00,000 - 14,00,000 P.A.', 'Full-time', 'Python, Django, Celery, PostgreSQL, Web Scraping, AWS', 'Responsible for building robust backend services, automated data pipelines, and microservice architectures.', 'Solid grasp of asynchronous programming, database optimization, and cloud deployments.', '2027-05-10', 'https://example.com/apply/python-backend', 0, 'published', 1),
(5, 11, 'Software Engineering Intern (Web & AI)', 'NextGen Innovations', 'nextgen.png', 'Bengaluru', 'On-site', '0 - 1 Year (Freshers welcome)', '25,000 - 35,000 / month Stipend', 'Internship', 'PHP / Python, HTML5, CSS3, JavaScript, MySQL, Git', 'Exciting 6-month internship with potential for full-time conversion. Work directly with experienced mentors on live production platforms.', 'Recent graduate or final year student in CS/IT/ECE with strong problem-solving skills.', '2027-03-25', 'https://example.com/apply/swe-intern', 1, 'published', 1),
(6, 6, 'Data Analyst (Power BI & SQL)', 'Insights Global', 'insights.png', 'Chennai', 'Hybrid', '1 - 4 Years', '6,50,000 - 10,00,000 P.A.', 'Full-time', 'SQL, Power BI, Advanced Excel, Python, DAX', 'Transform complex datasets into actionable business intelligence dashboards, KPI reports, and predictive models.', 'Bachelor\'s degree in Statistics, Mathematics, Economics, or Computer Science.', '2027-04-20', 'https://example.com/apply/data-analyst', 0, 'published', 1);

-- Seed Course Categories
INSERT INTO `course_categories` (`id`, `name`, `slug`, `description`, `icon`) VALUES
(1, 'PHP & MySQL', 'php-mysql', 'Full stack development with Core PHP, PDO, OOP, and MySQL', 'fa-code'),
(2, 'Python Programming', 'python-programming', 'Python fundamentals, OOP, scripts, data handling, and automation', 'fa-terminal'),
(3, 'Java Programming', 'java-programming', 'Core Java, Collections, OOP concepts, and enterprise principles', 'fa-coffee'),
(4, 'React.js', 'react-js', 'Modern React, Hooks, Component design, Context, and state', 'fa-atom'),
(5, 'Next.js', 'next-js', 'Full-stack React with App Router, SSR, SSG, and Server Actions', 'fa-cube'),
(6, 'JavaScript', 'javascript', 'Modern ES6+, DOM manipulation, Async/Await, and APIs', 'fa-js'),
(7, 'Data Analytics', 'data-analytics', 'Data cleaning, visualization, exploratory analysis, and storytelling', 'fa-chart-line'),
(8, 'Power BI', 'power-bi', 'Interactive dashboard design, DAX formulas, and BI reporting', 'fa-chart-bar'),
(9, 'Machine Learning', 'machine-learning', 'Scikit-learn, supervised/unsupervised learning, model evaluation', 'fa-brain'),
(10, 'Artificial Intelligence', 'artificial-intelligence', 'Foundations of AI, search algorithms, heuristic agents, reasoning', 'fa-network-wired'),
(11, 'Generative AI', 'generative-ai', 'Prompt engineering, diffusion models, LLM architectures, fine-tuning', 'fa-magic'),
(12, 'LangChain and LangGraph', 'langchain-langgraph', 'Building stateful agentic workflows, multi-agent teams, and RAG', 'fa-robot');

-- Seed Courses
INSERT INTO `courses` (`id`, `category_id`, `title`, `slug`, `thumbnail`, `description`, `instructor_name`, `skill_level`, `duration`, `total_lectures`, `language`, `playlist_url`, `is_featured`, `status`, `created_by`) VALUES
(1, 1, 'Master Core PHP & MySQL: Zero to Production', 'master-core-php-mysql-zero-to-production', 'course-php.jpg', 'Comprehensive hands-on course covering Core PHP, MySQL database design, secure PDO queries, authentication systems, session management, and building real-world dynamic web applications.', 'Mukesh Sahu', 'Beginner', '18 Hours', 8, 'English & Hindi', 'https://www.youtube.com/playlist?list=PLillGF-RfqbZ7s3t6ZInY3NjEOOX7NoBv', 1, 'published', 1),
(2, 11, 'Generative AI & LLM Masterclass with Python', 'generative-ai-llm-masterclass-python', 'course-genai.jpg', 'Deep dive into Generative AI, OpenAI & Gemini APIs, prompt engineering, vector databases (Chroma, Pinecone), retrieval-augmented generation (RAG), and deploying intelligent web apps.', 'Mukesh Sahu', 'Intermediate', '24 Hours', 6, 'English & Hindi', 'https://www.youtube.com/playlist?list=PLinedj3B30894zXo5lmsbUq7P3BfQ_vLz', 1, 'published', 1),
(3, 4, 'Modern React.js & Redux Toolkit Complete Guide', 'modern-react-redux-toolkit-guide', 'course-react.jpg', 'Master React 18+ from scratch. Learn components, props, hooks (useState, useEffect, useMemo, custom hooks), Redux Toolkit, client-side routing, and API integration with real projects.', 'Mukesh Sahu', 'Beginner', '16 Hours', 6, 'English & Hindi', 'https://www.youtube.com/playlist?list=PLu0W_9lII9agx63oZnT6IyhcMIbUMNMdt', 1, 'published', 1),
(4, 12, 'Autonomous AI Agents with LangChain & LangGraph', 'autonomous-ai-agents-langchain-langgraph', 'course-langchain.jpg', 'Learn to build production-grade autonomous agentic workflows using LangChain, LangGraph, tool-calling LLMs, structured outputs, memory persistence, and multi-agent coordination.', 'Mukesh Sahu', 'Advanced', '14 Hours', 4, 'English & Hindi', 'https://www.youtube.com/playlist?list=PLz-ep5ojrnQSNf0_9sBshWwG3f_VjOQvW', 1, 'published', 1);

-- Seed Course Modules
INSERT INTO `course_modules` (`id`, `course_id`, `title`, `description`, `order_index`) VALUES
(1, 1, 'Module 1: PHP Fundamentals & Syntax', 'Getting started with PHP syntax, variables, data types, and control structures', 1),
(2, 1, 'Module 2: Database Connectivity with PDO', 'Relational database schema, MySQL queries, and PDO prepared statements', 2),
(3, 1, 'Module 3: Authentication & Security Best Practices', 'Hashing, session security, CSRF defense, and role management', 3),
(4, 2, 'Module 1: Foundations of Generative AI & LLMs', 'Understanding transformers, tokens, temperature, and prompt engineering', 1),
(5, 2, 'Module 2: Building Production RAG Pipelines', 'Vector embeddings, semantic search, and document retrieval', 2),
(6, 3, 'Module 1: React Fundamentals & Hooks', 'JSX, component tree, state management, and lifecycle effects', 1),
(7, 4, 'Module 1: LangChain & LangGraph Architecture', 'Chains, prompt templates, agent graphs, and checkpointing', 1);

-- Seed Course Lectures (Real embedded educational YouTube videos)
INSERT INTO `course_lectures` (`id`, `course_id`, `module_id`, `title`, `description`, `youtube_video_id`, `duration_minutes`, `order_index`, `is_free_preview`) VALUES
(1, 1, 1, 'PHP Introduction & Environment Setup', 'How PHP works on Apache/Nginx, setting up XAMPP, and writing your first PHP script.', '2eebptXfEvw', 25, 1, 1),
(2, 1, 1, 'PHP Data Types, Variables & Conditionals', 'Deep dive into scalar and compound data types, operators, and conditional logic.', 'zZ6vybT1HQs', 30, 2, 0),
(3, 1, 2, 'Connecting to MySQL with PHP PDO', 'Why PDO is preferred over mysqli, DSN configuration, error handling, and prepared statements.', 'kEW6f7Pilc4', 35, 3, 0),
(4, 1, 2, 'Building CRUD Operations with Prepared Statements', 'Step-by-step implementation of Create, Read, Update, and Delete with SQL injection defense.', '3rAea_yLdNA', 40, 4, 0),
(5, 1, 3, 'Secure User Registration & Password Hashing', 'Implementing password_hash and password_verify with input sanitization.', 'gCo6JqGMi30', 30, 5, 0),
(6, 1, 3, 'Session Management, Cookies & CSRF Protection', 'Protecting user sessions, preventing session hijacking, and generating CSRF tokens.', 'V3pzpZ-V1gM', 35, 6, 0),
(7, 2, 4, 'Introduction to Large Language Models & APIs', 'Learn the basics of LLMs, tokenization, API keys, and interacting with modern AI models.', 'aircAruvnKk', 35, 1, 1),
(8, 2, 4, 'Effective Prompt Engineering Techniques', 'Master zero-shot, few-shot, chain-of-thought, and system instructions for reliable AI outputs.', 'jC4v5AS4RIM', 45, 2, 0),
(9, 2, 5, 'Vector Databases & Text Embeddings', 'Demystifying dense vector representations and storing embeddings in Chroma/Pinecone.', 'QdDoFfkVkcw', 50, 3, 0),
(10, 3, 6, 'React 18 Crash Course & Modern Architecture', 'Understanding virtual DOM, components, props, and standard Vite/React project setup.', 'w7ejDZ8SWv8', 45, 1, 1),
(11, 3, 6, 'State Management with useState & useEffect', 'Mastering synchronous state updates and managing side effects cleanly.', 'O6P86uwfdR0', 40, 2, 0),
(12, 4, 7, 'LangGraph Crash Course: Building Stateful AI Agents', 'Introduction to nodes, edges, state graphs, and conditional branching for autonomous agents.', '9Bpc6m0v7p4', 60, 1, 1);

-- Seed Course Enrollment for demo user
INSERT INTO `course_enrollments` (`id`, `user_id`, `course_id`, `progress_percentage`, `last_lecture_id`) VALUES
(1, 2, 1, 33.33, 1);

INSERT INTO `lecture_progress` (`user_id`, `course_id`, `lecture_id`, `is_completed`) VALUES
(2, 1, 1, 1),
(2, 1, 2, 1);

-- Seed AI Tool Categories
INSERT INTO `ai_tool_categories` (`id`, `name`, `slug`, `icon`, `description`) VALUES
(1, 'AI Chatbots', 'ai-chatbots', 'fa-comments', 'Conversational AI assistants for reasoning, problem-solving, and general tasks'),
(2, 'AI Coding Assistants', 'ai-coding-assistants', 'fa-code', 'Code generation, debugging, automated test creation, and pair programming'),
(3, 'AI Image Generators', 'ai-image-generators', 'fa-image', 'Text-to-image synthesis, photorealistic rendering, and artwork generation'),
(4, 'AI Video Generators', 'ai-video-generators', 'fa-video', 'Video creation, avatar animation, and text-to-video production tools'),
(5, 'AI Voice Generators', 'ai-voice-generators', 'fa-microphone', 'Realistic text-to-speech, voice cloning, and audio mastering'),
(6, 'AI Writing Tools', 'ai-writing-tools', 'fa-pen-nib', 'Copywriting, blog drafting, essay refinement, and grammatical enhancement'),
(7, 'AI Productivity Tools', 'ai-productivity-tools', 'fa-bolt', 'Meeting summarization, workflow automation, and knowledge synthesis'),
(8, 'AI Automation Tools', 'ai-automation-tools', 'fa-cogs', 'Autonomous agents, web scrapers, and multi-step process automations'),
(9, 'AI Data Analytics Tools', 'ai-data-analytics-tools', 'fa-chart-pie', 'Automated data exploration, predictive forecasting, and interactive charts');

-- Seed AI Tools
INSERT INTO `ai_tools` (`id`, `category_id`, `name`, `slug`, `logo`, `description`, `official_url`, `pricing_type`, `features_json`, `user_rating`, `is_featured`, `status`) VALUES
(1, 1, 'ChatGPT by OpenAI', 'chatgpt', 'chatgpt.png', 'The industry standard conversational LLM providing reasoning, math solving, coding assistance, and image analysis.', 'https://chat.openai.com', 'Freemium', '["Advanced GPT-4o model", "Custom GPTs creator", "Code Interpreter", "Web browsing & Vision"]', 4.90, 1, 'active'),
(2, 1, 'Claude by Anthropic', 'claude-ai', 'claude.png', 'Next-generation AI assistant known for exceptional writing quality, 200k token context, and nuanced coding.', 'https://claude.ai', 'Freemium', '["Artifacts interactive rendering", "200k context window", "Superior code comprehension", "Natural humanlike tone"]', 4.95, 1, 'active'),
(3, 2, 'GitHub Copilot', 'github-copilot', 'copilot.png', 'Your AI pair programmer built right into VS Code, JetBrains, and Neovim for inline autocomplete and chat explanations.', 'https://github.com/features/copilot', 'Paid', '["Real-time code completions", "Multi-file context editing", "Automated unit tests", "CLI command suggestions"]', 4.80, 1, 'active'),
(4, 2, 'Cursor AI Code Editor', 'cursor-ai', 'cursor.png', 'An AI-first code editor forked from VS Code that allows editing whole codebases with conversational commands.', 'https://cursor.com', 'Freemium', '["Full codebase indexing", "Multi-file diff generation", "Inline terminal debugging", "Zero latency autocompletion"]', 4.90, 1, 'active'),
(5, 3, 'Midjourney', 'midjourney', 'midjourney.png', 'Generates stunning, photorealistic images and digital illustrations from natural language descriptions.', 'https://midjourney.com', 'Paid', '["Photorealistic aesthetics", "Style referencing", "High resolution upscaling", "Pan and zoom controls"]', 4.90, 1, 'active'),
(6, 6, 'Grammarly AI', 'grammarly-ai', 'grammarly.png', 'Intelligent writing assistant that suggests clarity improvements, tone adjustments, and context-aware revisions.', 'https://grammarly.com', 'Freemium', '["Real-time grammar checks", "Tone detector", "Plagiarism scanner", "Generative rewrite suggestions"]', 4.70, 0, 'active'),
(7, 5, 'ElevenLabs', 'elevenlabs', 'elevenlabs.png', 'Leading voice AI platform offering realistic text-to-speech, synthetic accents, and voice cloning in 30+ languages.', 'https://elevenlabs.io', 'Freemium', '["Humanlike voice synthesis", "Instant voice cloning", "Dubbing studio", "Low latency audio API"]', 4.85, 1, 'active'),
(8, 7, 'Notion AI', 'notion-ai', 'notion-ai.png', 'Integrated AI productivity workspace helping teams summarize meeting notes, organize docs, and extract action items.', 'https://notion.so', 'Freemium', '["Document summarization", "Q&A across workspace docs", "Action item extraction", "Automated translation"]', 4.75, 0, 'active');

-- Seed Blog Categories
INSERT INTO `blog_categories` (`id`, `name`, `slug`, `description`) VALUES
(1, 'Programming', 'programming', 'Core principles, clean code, software design patterns, and debugging'),
(2, 'Web Development', 'web-development', 'Modern full-stack web engineering, frameworks, databases, and APIs'),
(3, 'Artificial Intelligence', 'artificial-intelligence', 'Breakthroughs in artificial intelligence, neural networks, and research'),
(4, 'Machine Learning', 'machine-learning', 'Practical ML workflows, data preprocessing, and algorithm deep dives'),
(5, 'Data Science', 'data-science', 'Big data, statistical analysis, visualization, and predictive science'),
(6, 'Career Guidance', 'career-guidance', 'Resume tips, interview preparation, portfolio building, and industry insights'),
(7, 'IT Industry News', 'it-industry-news', 'Tech trends, cloud migration, cybersecurity updates, and tech economy'),
(8, 'Tutorials', 'tutorials', 'Step-by-step practical guides to build real-world software applications');

-- Seed Blog Tags
INSERT INTO `blog_tags` (`id`, `name`, `slug`) VALUES
(1, 'PHP', 'php'),
(2, 'MySQL', 'mysql'),
(3, 'Artificial Intelligence', 'artificial-intelligence'),
(4, 'LangChain', 'langchain'),
(5, 'Web Security', 'web-security'),
(6, 'Career Advice', 'career-advice'),
(7, 'React', 'react'),
(8, 'Python', 'python');

-- Seed Blogs
INSERT INTO `blogs` (`id`, `category_id`, `author_id`, `title`, `slug`, `featured_image`, `excerpt`, `content`, `meta_title`, `meta_description`, `views_count`, `status`, `published_at`) VALUES
(1, 2, 1, 'Building High-Performance PHP Applications with PDO and Modern Architecture', 'building-high-performance-php-applications-pdo', 'blog-php.jpg', 'Learn best practices for structuring production-ready PHP applications, optimizing database interactions with PDO, and securing endpoints.', '<h2>Introduction to Modern PHP Development</h2><p>PHP has evolved dramatically over the last decade. With PHP 8.2 and 8.3 delivering JIT compilation, typed properties, enumerations, and readonly classes, PHP stands as one of the fastest and most reliable backend languages on the web.</p><h3>Why PDO is Essential for Enterprise Applications</h3><p>Using raw SQL concatenation is one of the most perilous security vulnerabilities in web programming. PDO (PHP Data Objects) provides a unified data-access abstraction layer and first-class prepared statements that completely neutralize SQL injection vulnerabilities.</p><pre><code>$stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE role_id = :role_id");\n$stmt->execute([":role_id" => 1]);\n$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);</code></pre><h3>Database Indexing and Connection Pooling</h3><p>Always ensure frequently filtered columns like <code>status</code>, <code>slug</code>, and foreign keys have proper indexes. Use persistent connections or lightweight connection wrappers to prevent overhead on high-traffic websites.</p>', 'Building High-Performance PHP Applications | Mukesh Sahu', 'A comprehensive guide on developing secure, maintainable, and lightning-fast PHP applications using PDO and clean architecture.', 342, 'published', '2026-10-15 10:00:00'),
(2, 3, 1, 'The Rise of Autonomous AI Agents: How LangChain & LangGraph Are Changing Software', 'rise-of-autonomous-ai-agents-langchain-langgraph', 'blog-ai.jpg', 'Discover how autonomous multi-agent systems are redefining automation, software engineering, and customer support workflows.', '<h2>Beyond Simple Chatbots</h2><p>While prompt-and-response chatbots transformed how we interact with technology, the next frontier belongs to <strong>Autonomous AI Agents</strong>. Agents are autonomous software programs driven by large language models that can reason, formulate plans, use external tools, query databases, and execute tasks iteratively.</p><h3>The Power of LangGraph for Stateful Workflows</h3><p>LangGraph introduces cyclical graph execution, allowing developers to model multi-agent collaboration with state persistence, human-in-the-loop validation, and time-travel debugging.</p><ul><li>Deterministic state machines combined with probabilistic LLM reasoning</li><li>Tool execution with strict schema validation</li><li>Built-in short-term and long-term memory checkpointing</li></ul><p>As developers, understanding how to orchestrate AI agents will be the defining technical competency of this decade.</p>', 'The Rise of Autonomous AI Agents | Mukesh Sahu', 'Explore how autonomous AI agents powered by LangChain and LangGraph are reshaping enterprise software and engineering workflows.', 518, 'published', '2026-10-22 14:30:00'),
(3, 6, 1, 'How to Land Your Dream IT Job in 2026: Roadmap & Interview Secrets', 'how-to-land-your-dream-it-job-roadmap', 'blog-career.jpg', 'A complete roadmap for engineers to crack technical interviews, build standout portfolios, and navigate today\'s competitive tech landscape.', '<h2>Navigating the Modern Tech Hiring Landscape</h2><p>Today\'s hiring managers are not just looking for code monkeys who memorize LeetCode problems; they want engineers who understand real business problems, write clean and maintainable code, and demonstrate the ability to learn continuously.</p><h3>1. Build Real, Deployed Projects</h3><p>A GitHub repository with a generic to-do list won\'t stand out. Instead, build and deploy end-to-end applications that solve actual problems—like an automated job tracker, an AI-powered document scanner, or an enterprise learning platform.</p><h3>2. Master Modern Version Control and Team Collaboration</h3><p>Demonstrate knowledge of feature branching, pull request reviews, semantic commit messages, and automated CI/CD pipelines.</p><h3>3. Continuous Learning</h3><p>Stay curious. Dive into cloud technologies, containerization, and AI tooling to make yourself indispensable in any engineering organization.</p>', 'How to Land Your Dream IT Job in 2026 | Mukesh Sahu', 'Strategic career roadmap, resume tips, and interview advice for software engineers and aspiring tech professionals.', 780, 'published', '2026-10-28 09:15:00'),
(4, 1, 1, 'Full-Stack Modern Web Engineering: Mastering React 19 and Next.js', 'full-stack-modern-web-engineering-react-nextjs', 'blog-react.jpg', 'Explore the modern JavaScript ecosystem with React 19 server actions, App Router paradigms, and scalable API integrations.', '<h2>The Evolution of Modern Frontend Architecture</h2><p>React 19 brings unified server actions, suspense boundaries, and optimized hydration to build lightning-fast web applications. Paired with modern backend services and clean database architectures, developers can deliver sub-second response times and seamless UX.</p><h3>Key Paradigms</h3><ul><li>Server Components for zero-bundle-size client delivery</li><li>Optimistic UI updates with modern React hooks</li><li>Robust error boundaries and progressive enhancement</li></ul>', 'Full-Stack Modern Web Engineering | Mukesh Sahu', 'Deep dive into React 19, server actions, and modern scalable full-stack web engineering.', 625, 'published', '2026-11-02 12:00:00');

-- Seed Blog Tag Relations
INSERT INTO `blog_tag_relations` (`blog_id`, `tag_id`) VALUES
(1, 1), (1, 2), (1, 5),
(2, 3), (2, 4), (2, 8),
(3, 6),
(4, 7), (4, 8);

-- Seed Website Settings
INSERT INTO `website_settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('site_name', 'MukeshSahu', 'general'),
('site_tagline', 'Personal Learning & Technology Platform', 'general'),
('site_email', 'contact@mukeshsahu.in', 'contact'),
('site_phone', '+91 98765 43210', 'contact'),
('site_address', 'Bengaluru, Karnataka, India', 'contact'),
('about_name', 'Mukesh Sahu', 'about'),
('about_title', 'Full Stack Software Engineer & Technology Educator', 'about'),
('about_bio', 'I am Mukesh Sahu, a dedicated software engineer, educator, and AI enthusiast with over 7 years of industry experience building high-traffic web applications, scalable cloud microservices, and modern AI-driven solutions. My mission is to bridge the gap between academic education and industry requirements by curating world-class learning content, handpicked IT job opportunities, and cutting-edge AI tools.', 'about'),
('about_education', '[{"degree": "Master of Computer Applications (MCA)", "institution": "Top Technical University", "year": "2018 - 2021", "details": "Specialized in Advanced Database Management, Software Engineering & Cloud Architectures."}, {"degree": "Bachelor of Science in Computer Science", "institution": "State University", "year": "2015 - 2018", "details": "Graduated with First Class Distinction. Led the university coding club."}]', 'about'),
('about_experience', '[{"role": "Lead Software Engineer", "company": "Tech Innovations Pvt Ltd", "period": "2023 - Present", "description": "Architected distributed web platforms serving 500k+ active users. Spearheaded AI integration and team mentorship."}, {"role": "Senior Full Stack Developer", "company": "CloudScale Systems", "period": "2021 - 2023", "description": "Built robust microservices with PHP/Laravel, Node.js, and React. Optimized MySQL query latency by 45%."}, {"role": "Software Developer", "company": "WebSphere Solutions", "period": "2019 - 2021", "description": "Developed dynamic CMS websites, REST APIs, and third-party payment gateway integrations."}]', 'about'),
('about_skills', '[{"name": "PHP & MySQL (PDO)", "level": 95}, {"name": "Python & FastApi", "level": 90}, {"name": "React.js & Next.js", "level": 88}, {"name": "AI & LangChain / LLMs", "level": 85}, {"name": "Cloud & Docker / DevOps", "level": 82}, {"name": "REST APIs & System Design", "level": 92}]', 'about'),
('social_linkedin', 'https://linkedin.com/in/mukeshsahu', 'social'),
('social_github', 'https://github.com/mukeshsahu', 'social'),
('social_youtube', 'https://youtube.com/@mukeshsahu', 'social'),
('social_twitter', 'https://twitter.com/mukeshsahu_tech', 'social'),
('seo_meta_title', 'MukeshSahu – Personal Learning & Technology Platform', 'seo'),
('seo_meta_description', 'Explore verified IT jobs, professional courses with video integration, top AI tools directory, and technical blogs by Mukesh Sahu.', 'seo'),
('seo_meta_keywords', 'Mukesh Sahu, IT Jobs, Web Development, PHP Courses, AI Tools, Technology Blog, Python, React', 'seo');

-- Seed Sample Contact Message
INSERT INTO `contact_messages` (`name`, `email`, `mobile`, `subject`, `message`, `is_read`, `created_at`) VALUES
('Rahul Sharma', 'rahul.sharma@example.com', '+91 9811122233', 'Collaboration on AI Course', 'Hi Mukesh, I really enjoyed your video on LangChain and would love to collaborate on an advanced agent workshop.', 0, '2026-11-01 11:20:00');
