CREATE TABLE IF NOT EXISTS valence_publishers (
    publisherid BIGSERIAL PRIMARY KEY,
    name TEXT UNIQUE,
    created TIMESTAMP DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS valence_publisher_tokens (
    keyid BIGSERIAL PRIMARY KEY,
    publisher BIGINT REFERENCES valence_publishers(publisherid),
    selector TEXT UNIQUE,
    validator TEXT,
    created TIMESTAMP DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS valence_publisher_publickeys (
    publickeyid BIGSERIAL PRIMARY KEY,
    publisher BIGINT REFERENCES valence_publishers(publisherid),
    chronicle_publish TEXT,
    chronicle_revoke TEXT NULL,
    revoked BOOLEAN DEFAULT FALSE,
    publickey TEXT
);
