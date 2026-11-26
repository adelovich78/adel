-- =========================================================
-- XY Academy — English Speaking Café
-- Schéma MySQL / MariaDB (compatible cPanel + phpMyAdmin)
-- Encodage : utf8mb4 — Moteur : InnoDB (transactions + verrous)
-- =========================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Entreprises clientes (comptes Corporate)
CREATE TABLE IF NOT EXISTS companies (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  contact_email VARCHAR(190) NULL,
  seats_included INT UNSIGNED NOT NULL DEFAULT 0,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Utilisateurs (tous rôles)
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role ENUM('student','parent','teacher','corporate','admin') NOT NULL DEFAULT 'student',
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL DEFAULT '',
  email VARCHAR(190) NULL UNIQUE,
  phone VARCHAR(40) NULL,
  password_hash VARCHAR(255) NULL,
  cefr_level ENUM('A1','A2','B1','B2','C1','C2') NOT NULL DEFAULT 'A1',
  birth_year SMALLINT UNSIGNED NULL,
  parent_id INT UNSIGNED NULL,          -- enfant rattaché à un parent
  company_id INT UNSIGNED NULL,         -- salarié rattaché à une entreprise
  goal VARCHAR(80) NULL,
  avatar VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  reset_token VARCHAR(64) NULL,
  reset_expires DATETIME NULL,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_role (role), KEY idx_parent (parent_id), KEY idx_company (company_id),
  CONSTRAINT fk_users_parent FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_users_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Profils professeurs natifs
CREATE TABLE IF NOT EXISTS teachers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  display_name VARCHAR(120) NOT NULL,
  origin VARCHAR(80) NOT NULL,              -- ex : London, UK
  accent VARCHAR(40) NOT NULL DEFAULT 'British',
  headline VARCHAR(190) NULL,
  bio TEXT NULL,
  specialties VARCHAR(255) NULL,            -- CSV : Business, Pronunciation...
  photo VARCHAR(255) NULL,
  hourly_rate DECIMAL(8,2) NOT NULL DEFAULT 55.00,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_teachers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Programmes (prix, capacités, niveaux éditables par l'admin)
CREATE TABLE IF NOT EXISTS programs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  category ENUM('group','one_to_one','table','business','pronunciation','kids','teens') NOT NULL,
  tagline VARCHAR(190) NULL,
  description TEXT NULL,
  audience VARCHAR(120) NULL,
  min_level ENUM('A1','A2','B1','B2','C1','C2') NOT NULL DEFAULT 'A1',
  max_level ENUM('A1','A2','B1','B2','C1','C2') NOT NULL DEFAULT 'C2',
  duration_min SMALLINT UNSIGNED NOT NULL DEFAULT 60,
  capacity TINYINT UNSIGNED NOT NULL DEFAULT 5,
  price DECIMAL(8,2) NOT NULL DEFAULT 25.00,
  membership_eligible TINYINT(1) NOT NULL DEFAULT 1,
  goals VARCHAR(255) NULL,                  -- CSV d'objectifs (conversation,career,...)
  color VARCHAR(20) NOT NULL DEFAULT 'green',
  image VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Séances planifiées (planning éditable)
CREATE TABLE IF NOT EXISTS sessions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  program_id INT UNSIGNED NOT NULL,
  teacher_id INT UNSIGNED NULL,
  title VARCHAR(160) NULL,
  level ENUM('A1','A2','B1','B2','C1','C2') NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  capacity TINYINT UNSIGNED NOT NULL DEFAULT 5,   -- max 5 élèves + 1 prof natif
  price DECIMAL(8,2) NULL,                        -- NULL = prix du programme
  room VARCHAR(60) NULL,
  mode ENUM('cafe','online') NOT NULL DEFAULT 'cafe',
  status ENUM('scheduled','cancelled','completed') NOT NULL DEFAULT 'scheduled',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_starts (starts_at), KEY idx_program (program_id), KEY idx_teacher (teacher_id),
  CONSTRAINT fk_sessions_program FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
  CONSTRAINT fk_sessions_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Formules d'abonnement (catalogue)
CREATE TABLE IF NOT EXISTS membership_plans (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(60) NOT NULL UNIQUE,
  name VARCHAR(80) NOT NULL,
  tagline VARCHAR(190) NULL,
  price DECIMAL(8,2) NOT NULL,
  period ENUM('month','quarter','year') NOT NULL DEFAULT 'month',
  credits SMALLINT UNSIGNED NOT NULL DEFAULT 4,   -- 0 = illimité
  includes_events TINYINT(1) NOT NULL DEFAULT 0,
  perks TEXT NULL,                                -- une ligne = un avantage
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Abonnements souscrits
CREATE TABLE IF NOT EXISTS memberships (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  plan_id INT UNSIGNED NOT NULL,
  status ENUM('pending','active','expired','cancelled') NOT NULL DEFAULT 'pending',
  starts_at DATE NULL,
  ends_at DATE NULL,
  credits_remaining SMALLINT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user (user_id),
  CONSTRAINT fk_mem_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_mem_plan FOREIGN KEY (plan_id) REFERENCES membership_plans(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Paiements (Stripe, sur place, crédit d'abonnement)
CREATE TABLE IF NOT EXISTS payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  purpose ENUM('booking','membership','event') NOT NULL,
  reference_id INT UNSIGNED NOT NULL,             -- booking / membership / event_registration
  amount DECIMAL(10,2) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'EUR',
  method ENUM('stripe','onsite','credit','free') NOT NULL DEFAULT 'stripe',
  status ENUM('pending','paid','failed','refunded','cancelled') NOT NULL DEFAULT 'pending',
  provider_ref VARCHAR(190) NULL,
  paid_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user (user_id), KEY idx_ref (purpose, reference_id),
  CONSTRAINT fk_pay_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Réservations (anti-surbooking + liste d'attente)
CREATE TABLE IF NOT EXISTS bookings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,                  -- participant (élève, enfant, salarié)
  booked_by INT UNSIGNED NOT NULL,                -- payeur (parent, corporate, soi-même)
  status ENUM('pending','confirmed','waitlist','cancelled','attended','no_show') NOT NULL DEFAULT 'pending',
  waitlist_position SMALLINT UNSIGNED NULL,
  level ENUM('A1','A2','B1','B2','C1','C2') NULL,
  goal VARCHAR(80) NULL,
  payment_id INT UNSIGNED NULL,
  membership_id INT UNSIGNED NULL,
  hold_expires_at DATETIME NULL,                  -- place réservée le temps du paiement
  teacher_note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  KEY idx_session_status (session_id, status), KEY idx_user (user_id),
  CONSTRAINT fk_book_session FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
  CONSTRAINT fk_book_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_book_by FOREIGN KEY (booked_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Événements (quiz, débat, cinéma, networking, book club, karaoké)
CREATE TABLE IF NOT EXISTS events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type ENUM('quiz','debate','movie','networking','book_club','karaoke','other') NOT NULL,
  title VARCHAR(160) NOT NULL,
  description TEXT NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NULL,
  capacity SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  price DECIMAL(8,2) NOT NULL DEFAULT 0,
  min_level ENUM('A1','A2','B1','B2','C1','C2') NOT NULL DEFAULT 'A2',
  location VARCHAR(160) NULL,
  image VARCHAR(255) NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_starts (starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_registrations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  status ENUM('pending','confirmed','waitlist','cancelled','attended') NOT NULL DEFAULT 'pending',
  payment_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_event_user (event_id, user_id),
  CONSTRAINT fk_er_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  CONSTRAINT fk_er_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Progression CEFR + compétences (0-100)
CREATE TABLE IF NOT EXISTS progress (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  teacher_id INT UNSIGNED NULL,
  session_id INT UNSIGNED NULL,
  cefr_level ENUM('A1','A2','B1','B2','C1','C2') NOT NULL,
  speaking TINYINT UNSIGNED NOT NULL DEFAULT 0,
  listening TINYINT UNSIGNED NOT NULL DEFAULT 0,
  pronunciation TINYINT UNSIGNED NOT NULL DEFAULT 0,
  vocabulary TINYINT UNSIGNED NOT NULL DEFAULT 0,
  confidence TINYINT UNSIGNED NOT NULL DEFAULT 0,
  source ENUM('teacher','ai','self') NOT NULL DEFAULT 'teacher',  -- prêt pour l'IA
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user_date (user_id, created_at),
  CONSTRAINT fk_prog_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_prog_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bibliothèque de livres par niveau
CREATE TABLE IF NOT EXISTS books (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  author VARCHAR(120) NULL,
  level ENUM('A1','A2','B1','B2','C1','C2') NOT NULL,
  category VARCHAR(60) NOT NULL DEFAULT 'Conversation',
  description TEXT NULL,
  file_path VARCHAR(255) NOT NULL,          -- relatif à storage/books/
  cover_color VARCHAR(20) NOT NULL DEFAULT '#0f4a37',
  cover_image VARCHAR(255) NULL,
  pages SMALLINT UNSIGNED NULL,
  downloads INT UNSIGNED NOT NULL DEFAULT 0,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS book_downloads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  book_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_bd_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
  CONSTRAINT fk_bd_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Réglages globaux éditables (prix affichés, coordonnées, règles)
CREATE TABLE IF NOT EXISTS settings (
  `key` VARCHAR(80) PRIMARY KEY,
  `value` TEXT NULL,
  label VARCHAR(160) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  topic VARCHAR(80) NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jetons API (future app mobile)
CREATE TABLE IF NOT EXISTS api_tokens (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  name VARCHAR(80) NULL,
  last_used_at DATETIME NULL,
  expires_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tok_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- File de traitements IA (prononciation, speaking practice) — préparé pour le futur
CREATE TABLE IF NOT EXISTS ai_jobs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type ENUM('pronunciation','speaking','placement') NOT NULL,
  input_path VARCHAR(255) NULL,
  prompt TEXT NULL,
  status ENUM('queued','processing','done','failed') NOT NULL DEFAULT 'queued',
  result_json MEDIUMTEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ai_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cours en ligne (future plateforme e-learning)
CREATE TABLE IF NOT EXISTS online_lessons (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  program_id INT UNSIGNED NULL,
  title VARCHAR(190) NOT NULL,
  level ENUM('A1','A2','B1','B2','C1','C2') NOT NULL,
  video_url VARCHAR(255) NULL,
  content MEDIUMTEXT NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
