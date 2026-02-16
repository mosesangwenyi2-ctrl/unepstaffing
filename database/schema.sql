-- Staff Skills Portal (Procedural) - SQL Schema
-- MySQL 5.7+ compatible

CREATE DATABASE IF NOT EXISTS staff_skills_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE staff_skills_portal;

CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS education_levels (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Duty stations table
CREATE TABLE IF NOT EXISTS duty_stations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed education levels
INSERT IGNORE INTO education_levels (name) VALUES
  ('Diploma'),
  ('Bachelor'),
  ('Master'),
  ('PhD'),
  ('Certificate');

-- Seed duty stations
INSERT IGNORE INTO duty_stations (name) VALUES
  ('Main Office'),
  ('NYC Office'),
  ('LA Office'),
  ('Chicago Office'),
  ('Houston Office'),
  ('Phoenix Office'),
  ('Philadelphia Office'),
  ('San Antonio Office'),
  ('San Diego Office'),
  ('Dallas Office'),
  ('San Jose Office'),
  ('Austin Office'),
  ('Jacksonville Office'),
  ('Fort Worth Office'),
  ('Columbus Office'),
  ('Charlotte Office'),
  ('SF Office'),
  ('Indianapolis Office'),
  ('Seattle Office'),
  ('Denver Office'),
  ('Boston Office');

-- Current locations table (managed by HR/Admin) - suggestions for "current location" entries
CREATE TABLE IF NOT EXISTS current_locations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed some common current location examples
INSERT IGNORE INTO current_locations (name) VALUES
  ('Working remotely from Kisii'),
  ('On field mission in Somalia'),
  ('On temporary assignment in Ethiopia'),
  ('On leave in Mombasa');

-- Languages table (admin-managed)
CREATE TABLE IF NOT EXISTS languages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Software/Tools expertise table (admin-managed)
CREATE TABLE IF NOT EXISTS software_expertise (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  category VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed sample languages
INSERT IGNORE INTO languages (name) VALUES
  ('English'),
  ('Spanish'),
  ('French'),
  ('German'),
  ('Mandarin'),
  ('Arabic'),
  ('Portuguese'),
  ('Japanese'),
  ('Italian'),
  ('Swahili');

-- Seed sample software expertise
INSERT IGNORE INTO software_expertise (name, category) VALUES
  ('Microsoft Excel', 'Office'),
  ('Microsoft Word', 'Office'),
  ('Python', 'Programming'),
  ('JavaScript', 'Programming'),
  ('Java', 'Programming'),
  ('PHP', 'Programming'),
  ('React', 'Web Framework'),
  ('Angular', 'Web Framework'),
  ('SQL', 'Database'),
  ('MySQL', 'Database'),
  ('Git', 'Version Control'),
  ('Docker', 'DevOps'),
  ('Tableau', 'Data Visualization'),
  ('Power BI', 'Data Visualization'),
  ('Salesforce', 'CRM');

-- Users table
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  index_number VARCHAR(50) NOT NULL UNIQUE,
  full_names VARCHAR(150) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  gender VARCHAR(20),
  current_location VARCHAR(120),
  highest_education VARCHAR(120),
  duty_station VARCHAR(120),
  availability_remote TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed basic roles
INSERT IGNORE INTO roles (name, description) VALUES
  ('Admin', 'Administrator with full access'),
  ('HR', 'Human Resources personnel'),
  ('Staff', 'Regular staff member');

-- Seed users: 1 Admin, 1 HR, 20 Staff (all with password User1234)
-- Hash: $2y$10$THxjELv9PUb/qD2RyLKbWO/1kDE6C3r.6thd11H2dMvqoysWIqEKy
INSERT IGNORE INTO users (index_number, full_names, email, password, role_id, gender, current_location, highest_education, duty_station, availability_remote, created_at) VALUES
  ('ADM001', 'John Admin', 'admin@example.com', '$2y$10$THxjELv9PUb/qD2RyLKbWO/1kDE6C3r.6thd11H2dMvqoysWIqEKy', 1, 'Male', 'Headquarters', 'PhD', 'Main Office', 1, NOW()),
  ('HR001', 'Sarah Human Resources', 'hr@example.com', '$2y$10$THxjELv9PUb/qD2RyLKbWO/1kDE6C3r.6thd11H2dMvqoysWIqEKy', 2, 'Female', 'Human Resources', 'Master', 'Main Office', 1, NOW()),
  ('STF001', 'Alice Johnson', 'alice@example.com', '$2y$10$THxjELv9PUb/qD2RyLKbWO/1kDE6C3r.6thd11H2dMvqoysWIqEKy', 3, 'Female', 'New York', 'Bachelor', 'NYC Office', 1, NOW()),
  ('STF002', 'Bob Smith', 'bob@example.com', '$2y$10$THxjELv9PUb/qD2RyLKbWO/1kDE6C3r.6thd11H2dMvqoysWIqEKy', 3, 'Male', 'Los Angeles', 'Bachelor', 'LA Office', 0, NOW()),
  ('STF003', 'Carol Williams', 'carol@example.com', '$2y$10$THxjELv9PUb/qD2RyLKbWO/1kDE6C3r.6thd11H2dMvqoysWIqEKy', 3, 'Female', 'Chicago', 'Master', 'Chicago Office', 1, NOW()),
  ('STF004', 'David Brown', 'david@example.com', '$2y$10$THxjELv9PUb/qD2RyLKbWO/1kDE6C3r.6thd11H2dMvqoysWIqEKy', 3, 'Male', 'Houston', 'Diploma', 'Houston Office', 0, NOW()),
  ('STF005', 'Emma Davis', 'emma@example.com', '$2y$10$THxjELv9PUb/qD2RyLKbWO/1kDE6C3r.6thd11H2dMvqoysWIqEKy', 3, 'Female', 'Phoenix', 'Bachelor', 'Phoenix Office', 1, NOW()),
  ('STF006', 'Frank Miller', 'frank@example.com', '$2y$10$THxjELv9PUb/qD2RyLKbWO/1kDE6C3r.6thd11H2dMvqoysWIqEKy', 3, 'Male', 'Philadelphia', 'Master', 'Philadelphia Office', 1, NOW()),
  ('STF007', 'Grace Wilson', 'grace@example.com', '$2y$10$THxjELv9PUb/qD2RyLKbWO/1kDE6C3r.6thd11H2dMvqoysWIqEKy', 3, 'Female', 'San Antonio', 'Bachelor', 'San Antonio Office', 0, NOW()),
  ('STF008', 'Henry Moore', 'henry@example.com', '$2y$10$THxjELv9PUb/qD2RyLKbWO/1kDE6C3r.6thd11H2dMvqoysWIqEKy', 3, 'Male', 'San Diego', 'Diploma', 'San Diego Office', 1, NOW()),
  ('STF009', 'Iris Taylor', 'iris@example.com', '$2y$10$THxjELv9PUb/qD2RyLKbWO/1kDE6C3r.6thd11H2dMvqoysWIqEKy', 3, 'Female', 'Dallas', 'Master', 'Dallas Office', 1, NOW()),
  ('STF010', 'Jack Anderson', 'jack@example.com', '$2y$10$THxjELv9PUb/qD2RyLKbWO/1kDE6C3r.6thd11H2dMvqoysWIqEKy', 3, 'Male', 'San Jose', 'Bachelor', 'San Jose Office', 0, NOW()),
  ('STF011', 'Karen Thomas', 'karen@example.com', '$2y$10$THxjELv9PUb/qD2RyLKbWO/1kDE6C3r.6thd11H2dMvqoysWIqEKy', 3, 'Female', 'Austin', 'Master', 'Austin Office', 1, NOW()),
  ('STF012', 'Leo Jackson', 'leo@example.com', '$2y$10$THxjELv9PUb/qD2RyLKbWO/1kDE6C3r.6thd11H2dMvqoysWIqEKy', 3, 'Male', 'Jacksonville', 'Diploma', 'Jacksonville Office', 0, NOW()),
  ('STF013', 'Maria White', 'maria@example.com', '$2y$10$THxjELv9PUb/qD2RyLKbWO/1kDE6C3r.6thd11H2dMvqoysWIqEKy', 3, 'Female', 'Fort Worth', 'Bachelor', 'Fort Worth Office', 1, NOW()),
  ('STF014', 'Nathan Harris', 'nathan@example.com', '$2y$10$THxjELv9PUb/qD2RyLKbWO/1kDE6C3r.6thd11H2dMvqoysWIqEKy', 3, 'Male', 'Columbus', 'Master', 'Columbus Office', 1, NOW()),
  ('STF015', 'Olivia Martin', 'olivia@example.com', '$2y$10$THxjELv9PUb/qD2RyLKbWO/1kDE6C3r.6thd11H2dMvqoysWIqEKy', 3, 'Female', 'Charlotte', 'Bachelor', 'Charlotte Office', 0, NOW()),
  ('STF016', 'Paul Thompson', 'paul@example.com', '$2y$10$THxjELv9PUb/qD2RyLKbWO/1kDE6C3r.6thd11H2dMvqoysWIqEKy', 3, 'Male', 'San Francisco', 'Master', 'SF Office', 1, NOW()),
  ('STF017', 'Quinn Lee', 'quinn@example.com', '$2y$10$THxjELv9PUb/qD2RyLKbWO/1kDE6C3r.6thd11H2dMvqoysWIqEKy', 3, 'Female', 'Indianapolis', 'Diploma', 'Indianapolis Office', 0, NOW()),
  ('STF018', 'Rachel Green', 'rachel@example.com', '$2y$10$THxjELv9PUb/qD2RyLKbWO/1kDE6C3r.6thd11H2dMvqoysWIqEKy', 3, 'Female', 'Seattle', 'Bachelor', 'Seattle Office', 1, NOW()),
  ('STF019', 'Samuel Clark', 'samuel@example.com', '$2y$10$THxjELv9PUb/qD2RyLKbWO/1kDE6C3r.6thd11H2dMvqoysWIqEKy', 3, 'Male', 'Denver', 'Master', 'Denver Office', 1, NOW()),
  ('STF020', 'Tina Lewis', 'tina@example.com', '$2y$10$THxjELv9PUb/qD2RyLKbWO/1kDE6C3r.6thd11H2dMvqoysWIqEKy', 3, 'Female', 'Boston', 'Diploma', 'Boston Office', 0, NOW());

-- User languages (pivot table with proficiency)
CREATE TABLE IF NOT EXISTS user_languages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  language_id INT NOT NULL,
  proficiency VARCHAR(50) NOT NULL DEFAULT 'Intermediate',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_language (user_id, language_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User software expertise (pivot table with proficiency and experience)
CREATE TABLE IF NOT EXISTS user_software_expertise (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  software_expertise_id INT NOT NULL,
  proficiency VARCHAR(50) NOT NULL DEFAULT 'Intermediate',
  years_experience DECIMAL(3,1),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_software (user_id, software_expertise_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (software_expertise_id) REFERENCES software_expertise(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Projects portfolio (user-created)
CREATE TABLE IF NOT EXISTS projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  project_name VARCHAR(200) NOT NULL,
  description TEXT,
  technologies_used TEXT,
  start_date DATE,
  end_date DATE,
  role_responsibilities TEXT,
  project_link VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Seed: User Language Proficiency
-- ============================================
INSERT IGNORE INTO user_languages (user_id, language_id, proficiency) SELECT users.id, languages.id, 'Advanced' FROM users, languages WHERE users.index_number = 'STF001' AND languages.name = 'English';
INSERT IGNORE INTO user_languages (user_id, language_id, proficiency) SELECT users.id, languages.id, 'Intermediate' FROM users, languages WHERE users.index_number = 'STF001' AND languages.name = 'Spanish';
INSERT IGNORE INTO user_languages (user_id, language_id, proficiency) SELECT users.id, languages.id, 'Beginner' FROM users, languages WHERE users.index_number = 'STF001' AND languages.name = 'French';

INSERT IGNORE INTO user_languages (user_id, language_id, proficiency) SELECT users.id, languages.id, 'Advanced' FROM users, languages WHERE users.index_number = 'STF002' AND languages.name = 'English';
INSERT IGNORE INTO user_languages (user_id, language_id, proficiency) SELECT users.id, languages.id, 'Advanced' FROM users, languages WHERE users.index_number = 'STF002' AND languages.name = 'Mandarin';

INSERT IGNORE INTO user_languages (user_id, language_id, proficiency) SELECT users.id, languages.id, 'Advanced' FROM users, languages WHERE users.index_number = 'STF003' AND languages.name = 'English';
INSERT IGNORE INTO user_languages (user_id, language_id, proficiency) SELECT users.id, languages.id, 'Fluent' FROM users, languages WHERE users.index_number = 'STF003' AND languages.name = 'Spanish';
INSERT IGNORE INTO user_languages (user_id, language_id, proficiency) SELECT users.id, languages.id, 'Intermediate' FROM users, languages WHERE users.index_number = 'STF003' AND languages.name = 'Portuguese';

INSERT IGNORE INTO user_languages (user_id, language_id, proficiency) SELECT users.id, languages.id, 'Advanced' FROM users, languages WHERE users.index_number = 'STF004' AND languages.name = 'English';
INSERT IGNORE INTO user_languages (user_id, language_id, proficiency) SELECT users.id, languages.id, 'Intermediate' FROM users, languages WHERE users.index_number = 'STF004' AND languages.name = 'French';

INSERT IGNORE INTO user_languages (user_id, language_id, proficiency) SELECT users.id, languages.id, 'Advanced' FROM users, languages WHERE users.index_number = 'STF005' AND languages.name = 'English';
INSERT IGNORE INTO user_languages (user_id, language_id, proficiency) SELECT users.id, languages.id, 'Intermediate' FROM users, languages WHERE users.index_number = 'STF005' AND languages.name = 'Arabic';
INSERT IGNORE INTO user_languages (user_id, language_id, proficiency) SELECT users.id, languages.id, 'Beginner' FROM users, languages WHERE users.index_number = 'STF005' AND languages.name = 'Swahili';

INSERT IGNORE INTO user_languages (user_id, language_id, proficiency) SELECT users.id, languages.id, 'Advanced' FROM users, languages WHERE users.index_number = 'STF006' AND languages.name = 'English';
INSERT IGNORE INTO user_languages (user_id, language_id, proficiency) SELECT users.id, languages.id, 'Advanced' FROM users, languages WHERE users.index_number = 'STF006' AND languages.name = 'German';

INSERT IGNORE INTO user_languages (user_id, language_id, proficiency) SELECT users.id, languages.id, 'Advanced' FROM users, languages WHERE users.index_number = 'STF007' AND languages.name = 'English';
INSERT IGNORE INTO user_languages (user_id, language_id, proficiency) SELECT users.id, languages.id, 'Intermediate' FROM users, languages WHERE users.index_number = 'STF007' AND languages.name = 'Japanese';

INSERT IGNORE INTO user_languages (user_id, language_id, proficiency) SELECT users.id, languages.id, 'Advanced' FROM users, languages WHERE users.index_number = 'STF008' AND languages.name = 'English';
INSERT IGNORE INTO user_languages (user_id, language_id, proficiency) SELECT users.id, languages.id, 'Advanced' FROM users, languages WHERE users.index_number = 'STF008' AND languages.name = 'Italian';

-- ============================================
-- Seed: User Software Expertise
-- ============================================
INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Advanced', 5 FROM users, software_expertise se WHERE users.index_number = 'STF001' AND se.name = 'Python';
INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Advanced', 6 FROM users, software_expertise se WHERE users.index_number = 'STF001' AND se.name = 'JavaScript';
INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Intermediate', 3 FROM users, software_expertise se WHERE users.index_number = 'STF001' AND se.name = 'React';

INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Advanced', 7 FROM users, software_expertise se WHERE users.index_number = 'STF002' AND se.name = 'Microsoft Excel';
INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Advanced', 4 FROM users, software_expertise se WHERE users.index_number = 'STF002' AND se.name = 'SQL';
INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Advanced', 4 FROM users, software_expertise se WHERE users.index_number = 'STF002' AND se.name = 'MySQL';

INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Expert', 8 FROM users, software_expertise se WHERE users.index_number = 'STF003' AND se.name = 'Java';
INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Advanced', 5 FROM users, software_expertise se WHERE users.index_number = 'STF003' AND se.name = 'Git';
INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Intermediate', 2 FROM users, software_expertise se WHERE users.index_number = 'STF003' AND se.name = 'Docker';

INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Advanced', 6 FROM users, software_expertise se WHERE users.index_number = 'STF004' AND se.name = 'Tableau';
INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Advanced', 5 FROM users, software_expertise se WHERE users.index_number = 'STF004' AND se.name = 'Power BI';
INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Advanced', 4 FROM users, software_expertise se WHERE users.index_number = 'STF004' AND se.name = 'SQL';

INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Advanced', 7 FROM users, software_expertise se WHERE users.index_number = 'STF005' AND se.name = 'PHP';
INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Advanced', 6 FROM users, software_expertise se WHERE users.index_number = 'STF005' AND se.name = 'MySQL';
INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Intermediate', 3 FROM users, software_expertise se WHERE users.index_number = 'STF005' AND se.name = 'Git';

INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Advanced', 5 FROM users, software_expertise se WHERE users.index_number = 'STF006' AND se.name = 'JavaScript';
INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Advanced', 4 FROM users, software_expertise se WHERE users.index_number = 'STF006' AND se.name = 'Angular';
INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Intermediate', 2 FROM users, software_expertise se WHERE users.index_number = 'STF006' AND se.name = 'Git';

INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Advanced', 8 FROM users, software_expertise se WHERE users.index_number = 'STF007' AND se.name = 'Microsoft Excel';
INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Advanced', 7 FROM users, software_expertise se WHERE users.index_number = 'STF007' AND se.name = 'Microsoft Word';
INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Advanced', 5 FROM users, software_expertise se WHERE users.index_number = 'STF007' AND se.name = 'Salesforce';

INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Advanced', 6 FROM users, software_expertise se WHERE users.index_number = 'STF008' AND se.name = 'Python';
INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Advanced', 5 FROM users, software_expertise se WHERE users.index_number = 'STF008' AND se.name = 'SQL';

INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Advanced', 4 FROM users, software_expertise se WHERE users.index_number = 'STF009' AND se.name = 'JavaScript';
INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Advanced', 3 FROM users, software_expertise se WHERE users.index_number = 'STF009' AND se.name = 'React';
INSERT IGNORE INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) SELECT users.id, se.id, 'Advanced', 4 FROM users, software_expertise se WHERE users.index_number = 'STF009' AND se.name = 'Git';

-- ============================================
-- Seed: User Projects
-- ============================================
INSERT IGNORE INTO projects (user_id, project_name, description, technologies_used, start_date, end_date, role_responsibilities, project_link) 
SELECT users.id, 'E-Commerce Platform Redesign', 'Complete redesign of product catalog and checkout system with improved UX', 'Python, React, PostgreSQL', '2023-03-01', '2023-09-30', 'Lead Developer', 'https://github.com/project/ecommerce' 
FROM users WHERE users.index_number = 'STF001';

INSERT IGNORE INTO projects (user_id, project_name, description, technologies_used, start_date, end_date, role_responsibilities, project_link) 
SELECT users.id, 'Data Migration Project', 'Migration of legacy system data to cloud infrastructure with zero downtime', 'Java, PostgreSQL, AWS', '2022-06-15', '2023-01-31', 'Senior Developer', 'https://internal.example.com/migration-doc'
FROM users WHERE users.index_number = 'STF003';

INSERT IGNORE INTO projects (user_id, project_name, description, technologies_used, start_date, end_date, role_responsibilities, project_link) 
SELECT users.id, 'Mobile CRM Application', 'Native iOS and Android CRM app for field sales teams', 'JavaScript, React Native, Node.js', '2023-01-10', '2023-08-15', 'Lead Mobile Developer', 'https://appstore.example.com/crm'
FROM users WHERE users.index_number = 'STF006';

INSERT IGNORE INTO projects (user_id, project_name, description, technologies_used, start_date, end_date, role_responsibilities, project_link) 
SELECT users.id, 'Business Intelligence Dashboard', 'Real-time analytics dashboard for executive reporting', 'Power BI, SQL Server, Excel', '2023-02-01', '2023-07-31', 'BI Developer', 'https://dashboard.internal.example.com'
FROM users WHERE users.index_number = 'STF004';

INSERT IGNORE INTO projects (user_id, project_name, description, technologies_used, start_date, end_date, role_responsibilities, project_link) 
SELECT users.id, 'Content Management System', 'Custom CMS for organization website with module-based architecture', 'PHP, MySQL, Bootstrap', '2022-09-01', '2023-05-31', 'Full Stack Developer', 'https://cms.example.com'
FROM users WHERE users.index_number = 'STF005';

INSERT IGNORE INTO projects (user_id, project_name, description, technologies_used, start_date, end_date, role_responsibilities, project_link) 
SELECT users.id, 'Marketing Analytics Tool', 'Internal tool for campaign tracking and ROI analysis', 'Tableau, SQL, Python', '2023-04-01', NULL, 'Analytics Developer', 'https://internal-analytics.example.com'
FROM users WHERE users.index_number = 'STF004';

INSERT IGNORE INTO projects (user_id, project_name, description, technologies_used, start_date, end_date, role_responsibilities, project_link) 
SELECT users.id, 'API Integration Framework', 'Standardized framework for third-party API integrations', 'Python, FastAPI, Docker', '2023-05-01', NULL, 'Backend Architect', NULL
FROM users WHERE users.index_number = 'STF001';

INSERT IGNORE INTO projects (user_id, project_name, description, technologies_used, start_date, end_date, role_responsibilities, project_link) 
SELECT users.id, 'Performance Optimization Initiative', 'Database and application optimization reducing load times by 60%', 'Java, MySQL, JMeter', '2023-02-15', '2023-06-30', 'Performance Engineer', NULL
FROM users WHERE users.index_number = 'STF003';
