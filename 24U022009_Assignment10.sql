-- ============================================================
-- Assignment 10 - ICA2S 2026 Database Schema
-- Scholar Number: 24U022009
-- ============================================================

-- Table: Conference sections (from Assignment 9)
CREATE TABLE IF NOT EXISTS ConferenceSections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    section_name TEXT NOT NULL,
    content TEXT NOT NULL
);

-- Table: Users (new in Assignment 10 for authentication)
CREATE TABLE IF NOT EXISTS Users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,       -- Stored as bcrypt hash (PHP password_hash)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Seed: Conference Sections
-- ============================================================
INSERT INTO ConferenceSections (section_name, content) VALUES
('Home', 'Welcome to the 2026 International Conference on Advanced Systems (ICA2S). This premier event brings together researchers, scientists, and industry practitioners to explore the latest innovations in technology. Our mission is to foster collaboration across diverse engineering disciplines.'),

('Committee', '<h3 class="font-bold text-blue-700 mb-2">Steering Committee</h3><ul class="list-disc pl-5 mb-4"><li>Dr. Aris Thompson (Chair)</li><li>Dr. Sarah Jenkins (Co-Chair)</li></ul><h3 class="font-bold text-blue-700 mb-2">Technical Program Committee</h3><ul class="list-disc pl-5"><li>Prof. Michael Chen - MIT</li><li>Dr. Linda Ross - Stanford University</li><li>Dr. Kevin Patel - IIT Delhi</li></ul>'),

('Important Dates', '<div class="overflow-x-auto"><table class="w-full border text-left"><tr class="bg-gray-200"><th class="p-2 border">Event</th><th class="p-2 border">Date</th></tr><tr><td class="p-2 border">Paper Submission</td><td class="p-2 border">October 15, 2025</td></tr><tr><td class="p-2 border">Acceptance Notification</td><td class="p-2 border">November 20, 2025</td></tr><tr><td class="p-2 border">Camera Ready Paper</td><td class="p-2 border">December 01, 2025</td></tr><tr><td class="p-2 border">Conference Dates</td><td class="p-2 border">February 26-28, 2026</td></tr></table></div>'),

('Speakers', '<strong>Keynote Speaker 1:</strong> Dr. Elena Rodriguez - "The Future of Quantum Computing in AI".<br><br><strong>Keynote Speaker 2:</strong> Mr. Julian Vane - "Sustainable Infrastructure for Smart Cities".'),

('Workshop', 'Join our full-day workshop on "Cloud-Native Architectures" led by industry experts from Google and AWS. Participants will receive a certificate of completion and hands-on lab access.'),

('Submission', 'All papers must be original and not simultaneously submitted to another journal or conference. Submissions should be made through the EasyChair portal. Use the standard double-column IEEE template.'),

('Special Session', 'We are hosting a special track on "Cyber-Physical Systems in Healthcare." If you wish to lead a sub-session, please contact the secretariat with your proposal by November 1st.'),

('Registration', '<ul class="list-disc pl-5 mb-4"><li>Regular Author: $450</li><li>Student Author: $250</li><li>Attendee: $150</li></ul><p>Late registration after Jan 1st will incur a $100 surcharge.</p>'),

('Sponsorship', 'Elevate your brand by sponsoring ICA2S 2026. We offer Diamond, Gold, and Silver tiers. Benefits include logo placement on the website, dedicated exhibit booths, and speaking slots.'),

('Contact', 'For general inquiries: info@ica2s.vercel.app<br>For submission help: support@ica2s.vercel.app<br>Phone: +1 (555) 123-4567');

-- ============================================================
-- Note on Users table:
-- Passwords are stored as PHP bcrypt hashes using password_hash().
-- Do NOT store plain-text passwords.
-- Example insert (for testing only — use the registration form):
--
-- INSERT INTO Users (username, email, password) VALUES
-- ('testuser', 'test@example.com', '$2y$10$...<bcrypt_hash>...');
-- ============================================================
