
FROM ubuntu:24.04

ARG MOODLE_BRANCH
ARG PHP_VERSION
ARG NODE_VERSION

# Install dependencies
RUN apt-get update && \
    apt-get install -y lsb-release ca-certificates apt-transport-https software-properties-common curl

# Add the official PHP repository
RUN add-apt-repository ppa:ondrej/php

# Install the specified PHP version and common extensions
RUN apt-get update && \
    apt-get install -y php${PHP_VERSION} php${PHP_VERSION}-cli php${PHP_VERSION}-xml php${PHP_VERSION}-mbstring php${PHP_VERSION}-zip php${PHP_VERSION}-pgsql php${PHP_VERSION}-mysql php${PHP_VERSION}-gd php${PHP_VERSION}-soap php${PHP_VERSION}-xmlrpc php${PHP_VERSION}-curl php${PHP_VERSION}-intl


# Symlink for default 'php' command
RUN update-alternatives --set php /usr/bin/php${PHP_VERSION}

# Set PHP max_input_vars to at least 5000 for CLI and FPM (if installed)
RUN echo 'max_input_vars=5000' > /etc/php/${PHP_VERSION}/cli/conf.d/99-max-input-vars.ini \
        && if [ -d "/etc/php/${PHP_VERSION}/fpm/conf.d" ]; then \
                 cp /etc/php/${PHP_VERSION}/cli/conf.d/99-max-input-vars.ini /etc/php/${PHP_VERSION}/fpm/conf.d/; \
             fi


# Install Node.js (specific version) and other dependencies
RUN curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash -
RUN apt-get install -y nodejs git unzip curl locales postgresql mariadb-client
RUN locale-gen en_AU.UTF-8

# Install Composer (official recommended method)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Clone Moodle core (example: main branch)
RUN git clone --depth 1 --branch ${MOODLE_BRANCH} https://github.com/moodle/moodle.git /moodle

# Install moodle-plugin-ci
RUN composer create-project -n --no-dev --prefer-dist moodlehq/moodle-plugin-ci /ci ^4

# Install Grunt CLI globally
RUN npm install -g grunt-cli

# Install Docker CLI for behat
RUN apt-get update && apt-get install -y docker.io

# Optionally, add your user to the docker group
RUN groupadd -for docker && usermod -aG docker www-data

# Add ci/bin and ci/vendor/bin to PATH
ENV PATH="/ci/bin:/ci/vendor/bin:${PATH}"

RUN mkdir -p /workspace

# Set working directory
WORKDIR /workspace

# Example: Set build args as environment variables
ENV MOODLE_BRANCH=${MOODLE_BRANCH}
ENV PHP_VERSION=${PHP_VERSION}
ENV NODE_VERSION=${NODE_VERSION}