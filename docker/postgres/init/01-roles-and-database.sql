-- Local-development and CI bootstrap for the two Postgres roles the app uses.
--
-- Passwords are fixed, local-only values matching .env.example and
-- .env.testing: fine for a disposable local container, never for anything
-- real. Runs once, the first time the postgres data volume is initialised.

-- starter_app_migrator: owns the tables, holds BYPASSRLS. Used only by
-- migrations, seeders, backfills and pg_dump, never by the running application.
CREATE ROLE starter_app_migrator WITH LOGIN PASSWORD 'starter_app_migrator_local_only' CREATEDB BYPASSRLS;

-- starter_app_app: the only role the running application authenticates as.
-- An ordinary role, fully subject to RLS, never granted BYPASSRLS.
CREATE ROLE starter_app_app WITH LOGIN PASSWORD 'starter_app_app_local_only';

CREATE DATABASE starter_app OWNER starter_app_migrator;

-- A separate database for the automated test suite, so `composer ci:check`
-- never reads or writes local development data.
CREATE DATABASE starter_app_test OWNER starter_app_migrator;

\connect starter_app

GRANT CONNECT ON DATABASE starter_app TO starter_app_app;
GRANT USAGE ON SCHEMA public TO starter_app_app;

-- Tables and sequences created later by the migrator's migrations become
-- usable by the app role without a per-migration GRANT. (Tables that already
-- exist, for example when adding this to a populated database, need a one-off
-- GRANT ... ON ALL TABLES IN SCHEMA public.)
ALTER DEFAULT PRIVILEGES FOR ROLE starter_app_migrator IN SCHEMA public
  GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO starter_app_app;
ALTER DEFAULT PRIVILEGES FOR ROLE starter_app_migrator IN SCHEMA public
  GRANT USAGE, SELECT ON SEQUENCES TO starter_app_app;

\connect starter_app_test

GRANT CONNECT ON DATABASE starter_app_test TO starter_app_app;
GRANT USAGE ON SCHEMA public TO starter_app_app;

ALTER DEFAULT PRIVILEGES FOR ROLE starter_app_migrator IN SCHEMA public
  GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO starter_app_app;
ALTER DEFAULT PRIVILEGES FOR ROLE starter_app_migrator IN SCHEMA public
  GRANT USAGE, SELECT ON SEQUENCES TO starter_app_app;
