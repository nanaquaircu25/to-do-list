-- Create the users table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create the todos table
-- Create the todos table with a status column
CREATE TABLE todos (
    id SERIAL PRIMARY KEY,
    task VARCHAR(255) NOT NULL,
    description TEXT,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    status VARCHAR(10) DEFAULT 'pending', -- Add status with default value 'pending'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

