-- Netflix Clone Database Schema
-- Created for data storage and management

-- Database creation (if needed)
-- CREATE DATABASE IF NOT EXISTS dbgk2bqd1ybqww;
-- USE dbgk2bqd1ybqww;

-- Drop tables if they exist (for clean setup)
DROP TABLE IF EXISTS movies;
DROP TABLE IF EXISTS users;

-- Create users table
CREATE TABLE users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create movies table
CREATE TABLE movies (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    genre VARCHAR(50) NOT NULL,
    video_url VARCHAR(255) NOT NULL,
    thumbnail VARCHAR(255) NOT NULL,
    user_id INT(11) NOT NULL,
    category VARCHAR(50) DEFAULT 'regular',
    year VARCHAR(4) DEFAULT '2025',
    duration VARCHAR(10) DEFAULT '1h 30m',
    rating VARCHAR(10) DEFAULT 'PG-13',
    views INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert sample users
INSERT INTO users (name, email, password) VALUES
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'), -- password: password
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'), -- password: password
('Admin User', 'admin@netflix.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- password: password

-- Insert sample movies
INSERT INTO movies (title, description, genre, video_url, thumbnail, user_id, category, year, duration, rating, views) VALUES
('The Great Adventure', 'An epic journey through unknown lands filled with mystery and excitement.', 'Adventure', 'uploads/videos/sample_video1.mp4', 'uploads/thumbnails/sample_thumb1.jpg', 1, 'featured', '2024', '2h 15m', 'PG-13', 1250),
('Mystery Manor', 'A thrilling detective story set in an old mansion with dark secrets.', 'Mystery', 'uploads/videos/sample_video2.mp4', 'uploads/thumbnails/sample_thumb2.jpg', 1, 'regular', '2023', '1h 45m', 'R', 890),
('Comedy Central', 'Laugh out loud with this hilarious comedy featuring top comedians.', 'Comedy', 'uploads/videos/sample_video3.mp4', 'uploads/thumbnails/sample_thumb3.jpg', 2, 'trending', '2024', '1h 30m', 'PG', 2100),
('Sci-Fi Horizon', 'Explore the future in this groundbreaking science fiction masterpiece.', 'Sci-Fi', 'uploads/videos/sample_video4.mp4', 'uploads/thumbnails/sample_thumb4.jpg', 2, 'featured', '2023', '2h 30m', 'PG-13', 1560),
('Romance in Paris', 'A beautiful love story set against the backdrop of the City of Light.', 'Romance', 'uploads/videos/sample_video5.mp4', 'uploads/thumbnails/sample_thumb5.jpg', 3, 'regular', '2024', '1h 55m', 'PG-13', 980),
('Action Heroes', 'Non-stop action and adventure with the world\'s greatest heroes.', 'Action', 'uploads/videos/sample_video6.mp4', 'uploads/thumbnails/sample_thumb6.jpg', 3, 'trending', '2023', '2h 5m', 'R', 1870);

-- Create indexes for better performance
CREATE INDEX idx_movies_genre ON movies(genre);
CREATE INDEX idx_movies_category ON movies(category);
CREATE INDEX idx_movies_year ON movies(year);
CREATE INDEX idx_movies_views ON movies(views);
CREATE INDEX idx_users_email ON users(email);

-- Show table structure
DESCRIBE users;
DESCRIBE movies;

-- Show sample data
SELECT 'Users Table:' as Table_Name;
SELECT * FROM users;

SELECT 'Movies Table:' as Table_Name;
SELECT * FROM movies; 