ARG POSTGRES_VERSION=15
FROM postgres:${POSTGRES_VERSION}

ARG MOODLE_BRANCH
ARG MOODLE_MINOR
ARG PHP_VERSION
ARG POSTGRES_VERSION

ENV MOODLE_BRANCH=${MOODLE_BRANCH}
ENV MOODLE_MINOR=${MOODLE_MINOR}
ENV PHP_VERSION=${PHP_VERSION}
ENV POSTGRES_VERSION=${POSTGRES_VERSION}

LABEL org.opencontainers.image.source="https://github.com/catalyst/catalyst-moodle-workflows"
LABEL org.opencontainers.image.description="Seeded PostgreSQL image for catalyst-moodle-workflows"
LABEL org.catalyst.moodle.branch="${MOODLE_BRANCH}"
LABEL org.catalyst.moodle.minor="${MOODLE_MINOR}"
LABEL org.catalyst.php.version="${PHP_VERSION}"
LABEL org.catalyst.pgsql.version="${POSTGRES_VERSION}"

COPY seed.sh /docker-entrypoint-initdb.d/20-seed.sh
COPY moodledata/ /opt/pgseed/moodledata/
RUN chmod +x /docker-entrypoint-initdb.d/20-seed.sh
RUN mkdir -p /pgseed \
  && tar -C /opt/pgseed -czf /pgseed/moodledata.tar.gz moodledata \
  && rm -rf /opt/pgseed
