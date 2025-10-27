INSERT INTO user_account (email, password_hash)
VALUES ('admin@studyhall.local', '$2y$10$Z4wI0pG9g1Ozi4t2m6g8pO0U4y3WZcV9n5F5xkQbP1B8xQ2rZ3Vq2')
ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);

SET @admin_id := LAST_INSERT_ID();

INSERT INTO user_profile (user_id, username)
VALUES (@admin_id, 'admin')
ON DUPLICATE KEY UPDATE username = VALUES(username);

INSERT INTO board (course_id, created_by, name, description)
VALUES
  (NULL, @admin_id, 'General Discussion',   'Ask questions, share tips'),
  (NULL, @admin_id, 'Programming Help',     'Get help with Python, Java, SQL, and other languages'),
  (NULL, @admin_id, 'Math & Algorithms',    'Discuss problem-solving strategies, proofs, and algorithm design'),
  (NULL, @admin_id, 'Study Resources',      'Share notes, flashcards, and helpful materials for your classes'),
  (NULL, @admin_id, 'Project Collaboration','Find partners and collaborate on coding or research projects'),
  (NULL, @admin_id, 'Career & Internships', 'Talk about resumes, interviews, and internship opportunities')
ON DUPLICATE KEY UPDATE
  description = VALUES(description),
  created_by  = VALUES(created_by);
