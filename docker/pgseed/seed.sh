#!/usr/bin/env bash
set -euo pipefail

MOODLE_BRANCH="${MOODLE_BRANCH:-unknown}"
MOODLE_MINOR="${MOODLE_MINOR:-unknown}"
PHP_VERSION="${PHP_VERSION:-unknown}"
POSTGRES_VERSION="${POSTGRES_VERSION:-unknown}"

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" <<-SQL
  CREATE DATABASE moodle;
SQL

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname moodle <<-SQL
  CREATE SCHEMA IF NOT EXISTS ci_seed;
  CREATE TABLE IF NOT EXISTS ci_seed.metadata (
      id BIGSERIAL PRIMARY KEY,
      moodle_branch TEXT NOT NULL,
      moodle_minor TEXT NOT NULL,
      php_version TEXT NOT NULL,
      pgsql_version TEXT NOT NULL,
      created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
  );
  INSERT INTO ci_seed.metadata (moodle_branch, moodle_minor, php_version, pgsql_version)
  VALUES ('${MOODLE_BRANCH}', '${MOODLE_MINOR}', '${PHP_VERSION}', '${POSTGRES_VERSION}');
SQL
