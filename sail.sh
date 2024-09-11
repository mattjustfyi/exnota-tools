#!/bin/bash

# Check if the sail script exists
if [ -x "./vendor/bin/sail" ]; then
    # Call the sail script with the provided arguments
    ./vendor/bin/sail "$@"
else
    echo "Error: ./vendor/bin/sail script not found."
fi