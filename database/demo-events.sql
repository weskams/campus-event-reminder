-- Sample events for local development and frontend work.
-- Run AFTER creating your first organiser account (create-organiser.php);
-- the events are assigned to organiser #1.

USE campus_event_reminder;

INSERT INTO events (organiser_id, title, description, starts_at, venue, category) VALUES
(1, 'Freshers Welcome Mixer',
    'Meet new classmates, club leaders and student council members. Snacks and music provided.',
    DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 16 HOUR, 'Main Hall', 'Social'),
(1, 'Inter-School Football Final',
    'School of Business vs School of Science and Engineering. Come support your team!',
    DATE_ADD(CURDATE(), INTERVAL 3 DAY) + INTERVAL 15 HOUR, 'University Sports Ground', 'Sports'),
(1, 'Research Methods Workshop',
    'A practical session on literature reviews, referencing and choosing a methodology for final-year projects.',
    DATE_ADD(CURDATE(), INTERVAL 5 DAY) + INTERVAL 10 HOUR, 'Library Seminar Room 2', 'Academic'),
(1, 'Tech Innovation Conference',
    'Talks from alumni working in software, data and cybersecurity, followed by a student project showcase.',
    DATE_ADD(CURDATE(), INTERVAL 9 DAY) + INTERVAL 9 HOUR, 'ICT Centre Auditorium', 'Academic'),
(1, 'Career Fair',
    'Employers from across Nakuru and Nairobi recruiting for internships and graduate roles. Bring your CV.',
    DATE_ADD(CURDATE(), INTERVAL 14 DAY) + INTERVAL 9 HOUR, 'Main Hall', 'General'),
(1, 'Cultural Night',
    'Food, dance and performances celebrating the many cultures on campus.',
    DATE_ADD(CURDATE(), INTERVAL 20 DAY) + INTERVAL 18 HOUR, 'Amphitheatre', 'Social');
