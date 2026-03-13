-- Query untuk validasi Per CS Average First Response Time
WITH sessions_in_range AS (
    SELECT DISTINCT session_id
    FROM messages
    WHERE timestamp::date = '2025-12-01'  -- Ganti dengan tanggal yang diinginkan
        AND session_id IS NOT NULL
),
customer_first_messages AS (
    SELECT
        m.session_id,
        MIN(m.timestamp) AS first_customer_time
    FROM messages m
    JOIN sessions_in_range s ON m.session_id = s.session_id
    WHERE m.role = 'customer'
        AND LOWER(m.message) != 'pengguna baru'
    GROUP BY m.session_id
),
cs_first AS (
    SELECT DISTINCT ON (m.session_id)
        m.session_id,
        m.timestamp AS first_cs_time,
        m.sender_username
    FROM messages m
    JOIN customer_first_messages cfm ON m.session_id = cfm.session_id
    WHERE m.role = 'customer_service'
        AND m.timestamp > cfm.first_customer_time
        AND m.sender_username NOT IN ('system', 'CS_MAXCHAT')
        AND LOWER(m.message) != 'pengguna baru'
        AND m.message NOT ILIKE '%Terima kasih, sobat! atas penilaian kamu%'
    ORDER BY m.session_id, m.timestamp ASC
),
final_calc AS (
    SELECT
        cfm.session_id,
        cfm.first_customer_time,
        cs.first_cs_time,
        cs.sender_username,
        EXTRACT(EPOCH FROM (cs.first_cs_time - cfm.first_customer_time)) AS response_time_seconds,
        ROUND(EXTRACT(EPOCH FROM (cs.first_cs_time - cfm.first_customer_time)) / 60, 2) AS response_time_minutes
    FROM customer_first_messages cfm
    JOIN cs_first cs ON cs.session_id = cfm.session_id
    WHERE NOT EXISTS (
        SELECT 1
        FROM messages m
        WHERE m.session_id = cfm.session_id
            AND m.role = 'customer_service'
            AND m.timestamp > cfm.first_customer_time
            AND m.timestamp < cs.first_cs_time
            AND m.sender_username NOT IN ('system', 'CS_MAXCHAT')
    )
)

-- PER CS BREAKDOWN
SELECT
    sender_username AS cs_name,
    COUNT(*) AS total_sessions,
    ROUND(AVG(response_time_minutes), 2) AS avg_response_minutes,
    FLOOR(AVG(response_time_seconds) / 60) || 'm ' ||
    FLOOR(AVG(response_time_seconds) % 60) || 's' AS avg_formatted,
    MIN(response_time_minutes) AS fastest_minutes,
    MAX(response_time_minutes) AS slowest_minutes
FROM final_calc
GROUP BY sender_username
ORDER BY avg_response_minutes ASC;
