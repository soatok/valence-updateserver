CREATE TABLE IF NOT EXISTS valence_projects (
    projectid BIGSERIAL PRIMARY KEY,
    owner BIGINT REFERENCES valence_publishers (publisherid),
    publisher TEXT,
    name TEXT,
    created TIMESTAMP DEFAULT NOW(),
    UNIQUE(name)
);

CREATE TABLE IF NOT EXISTS valence_project_channels (
    channelid BIGSERIAL PRIMARY KEY,
    name TEXT,
    value INTEGER DEFAULT 0,
    created TIMESTAMP DEFAULT NOW(),
    UNIQUE(NAME)
);

CREATE TABLE IF NOT EXISTS valence_project_updates (
    updateid BIGSERIAL PRIMARY KEY,
    project BIGINT REFERENCES valence_projects (projectid),
    publickey BIGINT REFERENCES valence_publisher_publickeys (publickeyid),
    channel BIGINT NULL REFERENCES valence_project_channels (channelid),
    publicid TEXT UNIQUE,
    signature TEXT,
    version TEXT,
    filepath TEXT,
    created TIMESTAMP DEFAULT NOW()
);
