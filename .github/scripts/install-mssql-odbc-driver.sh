#!/usr/bin/env bash

set -euo pipefail

# The sqlsrv PECL extension does not bundle Microsoft's ODBC driver.
# Install Driver 18 here because it supports the PHP versions and SQL Server
# releases used by this workflow, while keeping the package outside snapshots.
source /etc/os-release

case "$ID" in
  ubuntu)
    UBUNTU_VERSION="$VERSION_ID"
    ;;
  *)
    echo "Unsupported runner distribution for Microsoft ODBC Driver installation: $ID"
    exit 1
    ;;
esac

PACKAGE_FILE="$(mktemp --suffix=.deb)"
trap 'rm -f "$PACKAGE_FILE"' EXIT

curl -fsSL \
  "https://packages.microsoft.com/config/ubuntu/${UBUNTU_VERSION}/packages-microsoft-prod.deb" \
  -o "$PACKAGE_FILE"
sudo dpkg -i "$PACKAGE_FILE"
sudo apt-get update
sudo ACCEPT_EULA=Y apt-get install -y msodbcsql18

odbcinst -q -d -n "ODBC Driver 18 for SQL Server"
