ALTER TABLE valence_project_updates ADD COLUMN
    chronicle_publish TEXT;
ALTER TABLE valence_project_updates ADD COLUMN
    chronicle_revoke TEXT NULL;
ALTER TABLE valence_project_updates ADD COLUMN
    revoked BOOLEAN DEFAULT FALSE;
