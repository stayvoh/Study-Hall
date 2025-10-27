USE studyhall;
SET NAMES utf8mb4;

-- Users
INSERT INTO `user_account` (`id`, `email`, `password_hash`, `created_at`, `updated_at`) VALUES
(1, 'bnelso03@uafs.edu', '$2y$12$eOg2SzyFfYKWQ7XunWDMXu86MQBi6OPHWcf9RWclTOhNQVAymkTja', '2025-10-07 05:18:58', '2025-10-07 05:18:58');

-- Default Boards
INSERT INTO board (course_id, name, description)
VALUES 
  (NULL, 'General Discussion', 'Ask questions, share tips'),
  (NULL, 'Programming Help', 'Get help with Python, Java, SQL, and other languages'),
  (NULL, 'Math & Algorithms', 'Discuss problem-solving strategies, proofs, and algorithm design'),
  (NULL, 'Study Resources', 'Share notes, flashcards, and helpful materials for your classes'),
  (NULL, 'Project Collaboration', 'Find partners and collaborate on coding or research projects'),
  (NULL, 'Career & Internships', 'Talk about resumes, interviews, and internship opportunities')
ON DUPLICATE KEY UPDATE description = VALUES(description);
