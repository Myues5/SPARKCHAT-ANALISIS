-- Create agent_responses table
CREATE TABLE IF NOT EXISTS agent_responses (
    id SERIAL PRIMARY KEY,
    customer_name VARCHAR(255) NOT NULL,
    agent_name VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    first_response_time TIME NOT NULL,
    average_response_time TIME NOT NULL,
    resolved_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_agent_responses_agent_date ON agent_responses(agent_name, date);
CREATE INDEX IF NOT EXISTS idx_agent_responses_date ON agent_responses(date);

-- Insert dummy data
INSERT INTO agent_responses 
(customer_name, agent_name, date, first_response_time, average_response_time, resolved_time)
VALUES
('M1cH_N1k1J', 'Matthew', '2026-01-31', '00:02:31', '06:27:50', '06:27:50'),
('DIMSUMNESIA', 'Matthew', '2026-01-31', '00:02:23', '01:25:25', '01:25:25'),
('Masdoo', 'Matthew', '2026-01-31', '00:03:40', '00:42:53', '00:42:53'),
('pradan 0310', 'Matthew', '2026-01-31', '00:00:44', '00:26:55', '00:26:55'),
('Kwang Sen', 'Matthew', '2026-01-31', '00:00:32', '00:08:35', '00:08:35'),
('esacatering842', 'Matthew', '2026-01-31', '00:37:00', '00:02:38', '00:02:38'),
('Rina Store', 'Jessica', '2026-01-30', '00:01:15', '00:15:22', '00:15:22'),
('Andi Pratama', 'Jessica', '2026-01-30', '00:00:55', '00:10:44', '00:10:44'),
('Sari Collection', 'Daniel', '2026-01-29', '00:04:21', '00:55:12', '00:55:12'),
('Techno Mart', 'Daniel', '2026-01-29', '00:02:05', '00:18:45', '00:18:45');
