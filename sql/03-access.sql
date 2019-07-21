/* Exclusive access tokens (e.g. for alpha/beta channels) */
CREATE TABLE IF NOT EXISTS valence_access (
    accessid BIGSERIAL PRIMARY KEY,
    selector TEXT UNIQUE,
    validator TEXT,
    comment TEXT,
    created TIMESTAMP DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS valence_project_access (
    projectid BIGINT REFERENCES valence_projects (projectid),
    accessid BIGINT REFERENCES valence_access (accessid),
    channelmax INTEGER DEFAULT 0
);
