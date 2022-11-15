#!/usr/bin/env bash

# Get the current file/script path
SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )

# Output debugging
echo "Hello from bash!"
php -v

# Run the PHP script
php $SCRIPT_DIR/script.php "$1"
