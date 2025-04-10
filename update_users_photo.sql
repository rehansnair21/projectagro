-- Add photo column to users table
ALTER TABLE users
ADD COLUMN photo_url VARCHAR(255) DEFAULT 'default-avatar.png';
