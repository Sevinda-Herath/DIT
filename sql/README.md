# Nebula Esports Database - SQL Operations Guide

This comprehensive guide contains all the SQL commands you need to manage the Nebula Esports tournament platform database. Use these queries for development, testing, and database administration.

## 🚀 Getting Started

### Database Connection
```bash
# Login to MySQL
sudo mysql -u user_name -p

# Switch to the nebula database
USE `nebula`;
```

---

## 📊 Table Operations Guide

### 👥 Users Table Operations

The `users` table stores basic user account information including authentication credentials.

#### ➕ CREATE Operations
```sql
-- Insert new users
INSERT INTO `users` (`username`, `email`, `password_hash`, `created_at`) VALUES
('john_gamer', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW()),
('sarah_esports', 'sarah@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW()),
('mike_captain', 'mike@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW());
```

#### 📖 READ Operations
```sql
-- View all users
SELECT * FROM `users`;

-- View users with pagination (limit 10, skip first 0)
SELECT `id`, `username`, `email`, `created_at` FROM `users` 
ORDER BY `created_at` DESC 
LIMIT 10 OFFSET 0;

-- Find user by username
SELECT * FROM `users` WHERE `username` = 'john_gamer';

-- Find user by email
SELECT * FROM `users` WHERE `email` = 'sarah@example.com';

-- Count total users
SELECT COUNT(*) as total_users FROM `users`;
```

#### ✏️ UPDATE Operations
```sql
-- Change user email
UPDATE `users` 
SET `email` = 'john.newemail@example.com' 
WHERE `username` = 'john_gamer';

-- Change password hash
UPDATE `users` 
SET `password_hash` = '$2y$10$newHashHereWhenPasswordChanged' 
WHERE `id` = 1;
```

#### ❌ DELETE Operations
```sql
-- Remove user by ID (this will cascade to profiles and members)
DELETE FROM `users` WHERE `id` = 3;

-- Remove users created before a certain date
DELETE FROM `users` WHERE `created_at` < '2024-01-01';
```

---

### 👤 Profiles Table Operations

The `profiles` table contains extended user information including team details and game preferences.

#### ➕ CREATE Operations
```sql
-- Insert user profiles
INSERT INTO `profiles` (
    `user_id`, `full_name`, `dob`, `location`, `university`, `nic`, `mobile`,
    `team_name`, `team_captain`, `players_count`, `game_titles`, `team_logo_path`
) VALUES
(1, 'John Smith', '1995-06-15', 'Colombo', 'University of Colombo', '199512345678', '+94771234567', 
 'Thunder Hawks', 'John Smith', 4, '["pubg_mobile", "cod_pc"]', '/uploads/team_logos/thunder_hawks.png'),
(2, 'Sarah Johnson', '1997-03-22', 'Kandy', 'University of Peradeniya', '199712345679', '+94779876543',
 'Fire Phoenix', 'Sarah Johnson', 5, '["free_fire", "pubg_pc"]', '/uploads/team_logos/fire_phoenix.png');
```

#### 📖 READ Operations
```sql
-- View all profiles with user information
SELECT u.username, u.email, p.full_name, p.team_name, p.team_captain, p.players_count
FROM `users` u
LEFT JOIN `profiles` p ON u.id = p.user_id;

-- View profile by user ID
SELECT * FROM `profiles` WHERE `user_id` = 1;

-- Find teams by game title (JSON search)
SELECT p.team_name, p.team_captain, p.players_count, u.username
FROM `profiles` p
JOIN `users` u ON p.user_id = u.id
WHERE JSON_CONTAINS(p.game_titles, '"pubg_mobile"');

-- Find profiles by location
SELECT u.username, p.full_name, p.team_name, p.location
FROM `profiles` p
JOIN `users` u ON p.user_id = u.id
WHERE p.location = 'Colombo';

-- Count teams by player count
SELECT players_count, COUNT(*) as team_count
FROM `profiles`
WHERE players_count IS NOT NULL
GROUP BY players_count
ORDER BY players_count;
```

#### ✏️ UPDATE Operations
```sql
-- Update team information
UPDATE `profiles` 
SET `team_name` = 'Thunder Eagles', 
    `players_count` = 5,
    `game_titles` = '["pubg_mobile", "cod_pc", "free_fire"]'
WHERE `user_id` = 1;

-- Update personal information
UPDATE `profiles` 
SET `mobile` = '+94771111111',
    `location` = 'Galle'
WHERE `user_id` = 2;

-- Add team logo path
UPDATE `profiles` 
SET `team_logo_path` = '/uploads/team_logos/new_logo_123.png'
WHERE `user_id` = 1;
```

#### ❌ DELETE Operations
```sql
-- Remove profile (user data remains)
DELETE FROM `profiles` WHERE `user_id` = 2;
```

---

### 🎮 Members Table Operations

The `members` table stores individual team member information for each registered team.

#### ➕ CREATE Operations
```sql
-- Insert team members
INSERT INTO `members` (`user_id`, `idx`, `name`, `nic`, `email`, `phone`) VALUES
(1, 1, 'John Smith', '199512345678', 'john@example.com', '+94771234567'),
(1, 2, 'Alex Wilson', '199612345679', 'alex@example.com', '+94772234567'),
(1, 3, 'David Brown', '199712345680', 'david@example.com', '+94773234567'),
(1, 4, 'Chris Lee', '199812345681', 'chris@example.com', '+94774234567'),
(2, 1, 'Sarah Johnson', '199712345679', 'sarah@example.com', '+94779876543'),
(2, 2, 'Emma Davis', '199812345682', 'emma@example.com', '+94779876544'),
(2, 3, 'Lisa Miller', '199912345683', 'lisa@example.com', '+94779876545');
```

#### 📖 READ Operations
```sql
-- View all members for a specific team
SELECT m.idx, m.name, m.email, m.phone, u.username as team_owner
FROM `members` m
JOIN `users` u ON m.user_id = u.id
WHERE m.user_id = 1
ORDER BY m.idx;

-- View all teams with member count
SELECT u.username, p.team_name, COUNT(m.id) as actual_members, p.players_count as declared_count
FROM `users` u
LEFT JOIN `profiles` p ON u.id = p.user_id
LEFT JOIN `members` m ON u.id = m.user_id
GROUP BY u.id, u.username, p.team_name, p.players_count;

-- Find member by email across all teams
SELECT m.name, m.email, m.phone, u.username as team_owner, p.team_name
FROM `members` m
JOIN `users` u ON m.user_id = u.id
LEFT JOIN `profiles` p ON u.id = p.user_id
WHERE m.email = 'alex@example.com';

-- Count total members across all teams
SELECT COUNT(*) as total_members FROM `members`;
```

#### ✏️ UPDATE Operations
```sql
-- Update member information
UPDATE `members` 
SET `name` = 'Alexander Wilson',
    `phone` = '+94772222222'
WHERE `user_id` = 1 AND `idx` = 2;

-- Change member email
UPDATE `members` 
SET `email` = 'alex.wilson@newemail.com'
WHERE `id` = 2;
```

#### ❌ DELETE Operations
```sql
-- Remove a specific member
DELETE FROM `members` WHERE `user_id` = 1 AND `idx` = 4;

-- Remove all members for a team
DELETE FROM `members` WHERE `user_id` = 2;
```

---

### 🔐 Remember Tokens Table Operations

The `remember_tokens` table manages persistent login sessions for "Remember Me" functionality.

#### ➕ CREATE Operations
```sql
-- Insert remember tokens (usually done by application)
INSERT INTO `remember_tokens` (`user_id`, `selector`, `token_hash`, `expires_at`) VALUES
(1, 'abc123def456', 'hash_of_token_here_64_chars_long_example_hash_string_here_now', DATE_ADD(NOW(), INTERVAL 30 DAY)),
(2, 'ghi789jkl012', 'another_hash_of_token_here_64_chars_long_example_hash_string', DATE_ADD(NOW(), INTERVAL 30 DAY));
```

#### 📖 READ Operations
```sql
-- View all active remember tokens
SELECT r.selector, r.user_id, u.username, r.expires_at, r.created_at
FROM `remember_tokens` r
JOIN `users` u ON r.user_id = u.id
WHERE r.expires_at > NOW();

-- Find token by selector
SELECT * FROM `remember_tokens` WHERE `selector` = 'abc123def456';

-- Count tokens per user
SELECT u.username, COUNT(r.id) as token_count
FROM `users` u
LEFT JOIN `remember_tokens` r ON u.id = r.user_id AND r.expires_at > NOW()
GROUP BY u.id, u.username;
```

#### ✏️ UPDATE Operations
```sql
-- Extend token expiration (rare operation)
UPDATE `remember_tokens` 
SET `expires_at` = DATE_ADD(NOW(), INTERVAL 60 DAY)
WHERE `selector` = 'abc123def456';
```

#### ❌ DELETE Operations
```sql
-- Remove expired tokens (cleanup operation)
DELETE FROM `remember_tokens` WHERE `expires_at` < NOW();

-- Remove all tokens for a user (logout from all devices)
DELETE FROM `remember_tokens` WHERE `user_id` = 1;

-- Remove specific token
DELETE FROM `remember_tokens` WHERE `selector` = 'ghi789jkl012';
```

---

### 📧 Newsletter Subscriptions Table Operations

The `newsletter_subscriptions` table manages email newsletter subscribers.

#### ➕ CREATE Operations
```sql
-- Insert newsletter subscriptions
INSERT INTO `newsletter_subscriptions` (`email`, `ip`, `user_agent`) VALUES
('newsletter1@example.com', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
('newsletter2@example.com', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'),
('newsletter3@example.com', '192.168.1.102', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36');
```

#### 📖 READ Operations
```sql
-- View all newsletter subscriptions
SELECT * FROM `newsletter_subscriptions` ORDER BY `created_at` DESC;

-- View recent subscriptions (last 30 days)
SELECT `email`, `created_at` 
FROM `newsletter_subscriptions` 
WHERE `created_at` >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY `created_at` DESC;

-- Count total subscriptions
SELECT COUNT(*) as total_subscriptions FROM `newsletter_subscriptions`;

-- Subscriptions by month
SELECT 
    YEAR(created_at) as year,
    MONTH(created_at) as month,
    COUNT(*) as subscriptions
FROM `newsletter_subscriptions`
GROUP BY YEAR(created_at), MONTH(created_at)
ORDER BY year DESC, month DESC;

-- Find subscription by email
SELECT * FROM `newsletter_subscriptions` WHERE `email` = 'newsletter1@example.com';
```

#### ✏️ UPDATE Operations
```sql
-- Update email address (rare operation)
UPDATE `newsletter_subscriptions` 
SET `email` = 'updated.email@example.com'
WHERE `email` = 'newsletter1@example.com';
```

#### ❌ DELETE Operations
```sql
-- Remove subscription by email
DELETE FROM `newsletter_subscriptions` WHERE `email` = 'newsletter2@example.com';

-- Remove old subscriptions (cleanup, if needed)
DELETE FROM `newsletter_subscriptions` WHERE `created_at` < '2023-01-01';
```

---

### 💬 Contact Messages Table Operations

The `contact_messages` table stores messages submitted through the contact form.

#### ➕ CREATE Operations
```sql
-- Insert contact messages
INSERT INTO `contact_messages` (`full_name`, `email`, `subject`, `message`, `ip`, `user_agent`, `status`) VALUES
('John Doe', 'john.doe@example.com', 'Tournament Registration Issue', 'I am having trouble registering my team for the tournament. Can you help?', '192.168.1.100', 'Mozilla/5.0 Browser', 'not_replied'),
('Jane Smith', 'jane.smith@example.com', 'Team Logo Upload Problem', 'The team logo upload is not working properly.', '192.168.1.101', 'Mozilla/5.0 Browser', 'not_replied'),
('Mike Johnson', 'mike.j@example.com', 'Tournament Rules Question', 'I have questions about the tournament rules and regulations.', '192.168.1.102', 'Mozilla/5.0 Browser', 'replied');
```

#### 📖 READ Operations
```sql
-- View all contact messages
SELECT * FROM `contact_messages` ORDER BY `created_at` DESC;

-- View unread messages
SELECT `id`, `full_name`, `email`, `subject`, `created_at`
FROM `contact_messages` 
WHERE `status` = 'not_replied'
ORDER BY `created_at` ASC;

-- View messages by status
SELECT `status`, COUNT(*) as message_count
FROM `contact_messages`
GROUP BY `status`;

-- Recent messages (last 7 days)
SELECT `full_name`, `email`, `subject`, `created_at`
FROM `contact_messages`
WHERE `created_at` >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY `created_at` DESC;

-- Find messages by email
SELECT * FROM `contact_messages` WHERE `email` = 'john.doe@example.com';

-- Search messages by subject
SELECT `id`, `full_name`, `email`, `subject`, `created_at`
FROM `contact_messages`
WHERE `subject` LIKE '%tournament%'
ORDER BY `created_at` DESC;
```

#### ✏️ UPDATE Operations
```sql
-- Mark message as replied
UPDATE `contact_messages` 
SET `status` = 'replied'
WHERE `id` = 1;

-- Mark multiple messages as replied
UPDATE `contact_messages` 
SET `status` = 'replied'
WHERE `id` IN (1, 2, 3);
```

#### ❌ DELETE Operations
```sql
-- Remove old replied messages
DELETE FROM `contact_messages` 
WHERE `status` = 'replied' AND `created_at` < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Remove specific message
DELETE FROM `contact_messages` WHERE `id` = 3;
```

---

## 📈 Advanced Queries & Reports

### 🏆 Complete Team Information Report
```sql
-- Get complete team information with member details
SELECT 
    u.username,
    u.email,
    p.team_name,
    p.team_captain,
    p.players_count,
    p.game_titles,
    COUNT(m.id) as actual_members,
    GROUP_CONCAT(m.name ORDER BY m.idx SEPARATOR ', ') as member_names
FROM `users` u
LEFT JOIN `profiles` p ON u.id = p.user_id
LEFT JOIN `members` m ON u.id = m.user_id
WHERE p.team_name IS NOT NULL
GROUP BY u.id, u.username, u.email, p.team_name, p.team_captain, p.players_count, p.game_titles;
```

### 📊 User Activity Summary
```sql
-- Generate user activity summary
SELECT 
    u.id,
    u.username,
    u.email,
    u.created_at as registration_date,
    CASE WHEN p.user_id IS NOT NULL THEN 'Complete' ELSE 'Incomplete' END as profile_status,
    p.team_name,
    COUNT(m.id) as team_members,
    COUNT(r.id) as active_tokens
FROM `users` u
LEFT JOIN `profiles` p ON u.id = p.user_id
LEFT JOIN `members` m ON u.id = m.user_id
LEFT JOIN `remember_tokens` r ON u.id = r.user_id AND r.expires_at > NOW()
GROUP BY u.id, u.username, u.email, u.created_at, p.user_id, p.team_name;
```

### 🎮 Game Popularity Report
```sql
-- Analyze game popularity
SELECT 
    game_title,
    COUNT(*) as team_count
FROM (
    SELECT 
        p.user_id,
        JSON_UNQUOTE(JSON_EXTRACT(p.game_titles, CONCAT('$[', n.n, ']'))) as game_title
    FROM `profiles` p
    CROSS JOIN (
        SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4
    ) n
    WHERE JSON_EXTRACT(p.game_titles, CONCAT('$[', n.n, ']')) IS NOT NULL
) as game_extract
WHERE game_title IS NOT NULL
GROUP BY game_title
ORDER BY team_count DESC;
```

### 📅 Monthly Registration Statistics
```sql
-- Track monthly registration trends
SELECT 
    YEAR(created_at) as year,
    MONTH(created_at) as month,
    MONTHNAME(created_at) as month_name,
    COUNT(*) as new_users
FROM `users`
GROUP BY YEAR(created_at), MONTH(created_at)
ORDER BY year DESC, month DESC;
```

---

## 🛠️ Database Maintenance

### 📏 Database Size Analysis
```sql
-- Check database size by table
SELECT 
    table_name AS 'Table',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES 
WHERE table_schema = 'nebula'
ORDER BY (data_length + index_length) DESC;
```

### 📊 Table Row Counts
```sql
-- Check table row counts
SELECT 
    TABLE_NAME as 'Table',
    TABLE_ROWS as 'Row Count'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'nebula'
ORDER BY TABLE_ROWS DESC;
```

### ⚡ Database Optimization
```sql
-- Optimize all tables for better performance
OPTIMIZE TABLE `users`, `profiles`, `members`, `remember_tokens`, `newsletter_subscriptions`, `contact_messages`;
```

### 🔍 Orphaned Records Check
```sql
-- Check for orphaned records (data integrity)
SELECT 'Profiles without users' as check_type, COUNT(*) as count
FROM `profiles` p
LEFT JOIN `users` u ON p.user_id = u.id
WHERE u.id IS NULL

UNION ALL

SELECT 'Members without users' as check_type, COUNT(*) as count
FROM `members` m
LEFT JOIN `users` u ON m.user_id = u.id
WHERE u.id IS NULL

UNION ALL

SELECT 'Remember tokens without users' as check_type, COUNT(*) as count
FROM `remember_tokens` r
LEFT JOIN `users` u ON r.user_id = u.id
WHERE u.id IS NULL;
```

---

## 🧹 Data Cleanup Operations

> ⚠️ **Warning**: Use these cleanup operations with extreme caution! Always backup your database before running cleanup scripts.

### 🧪 Remove Test Data
```sql
-- Remove all test data (COMMENTED FOR SAFETY)
-- DELETE FROM `members` WHERE `user_id` IN (SELECT `id` FROM `users` WHERE `email` LIKE '%@example.com');
-- DELETE FROM `profiles` WHERE `user_id` IN (SELECT `id` FROM `users` WHERE `email` LIKE '%@example.com');
-- DELETE FROM `remember_tokens` WHERE `user_id` IN (SELECT `id` FROM `users` WHERE `email` LIKE '%@example.com');
-- DELETE FROM `users` WHERE `email` LIKE '%@example.com';
-- DELETE FROM `newsletter_subscriptions` WHERE `email` LIKE '%@example.com';
-- DELETE FROM `contact_messages` WHERE `email` LIKE '%@example.com';
```

---

## 💡 Tips & Best Practices

### 🔒 Security Notes
- Always use prepared statements in application code
- Never store plain text passwords
- Validate and sanitize all input data
- Use appropriate user permissions for database access

### 📊 Performance Tips
- Use LIMIT for large result sets
- Create indexes on frequently queried columns
- Regular database optimization and maintenance
- Monitor query performance with EXPLAIN

### 🗄️ Data Management
- Regular backups before major operations
- Test queries on development data first
- Use transactions for multi-table operations
- Document any custom modifications

---

## 🚀 Quick Reference

### Common Game Titles
- `pubg_mobile` - PUBG Mobile
- `free_fire` - Free Fire  
- `cod_pc` - Call of Duty (PC)
- `pubg_pc` - PUBG (PC)

### User Roles (if implemented)
- `user` - Regular tournament participant
- `organizer` - Event organizer
- `admin` - System administrator
- `head_admin` - Head administrator

### Message Status
- `not_replied` - New/unread message
- `replied` - Message has been responded to

---

*This guide covers all essential database operations for the Nebula Esports tournament platform. Always test queries in a development environment before running them in production.*