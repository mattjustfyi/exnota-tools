#!/bin/bash

# Starts Sail, Vite, and the queue worker.

# Function to stop all services
stop_all() {
    echo "Stopping all services..."
    ./vendor/bin/sail stop
    kill $VITE_PID
    exit 0
}

# Set up trap to catch SIGINT (Ctrl+C) and SIGTERM
trap stop_all SIGINT SIGTERM

# Start Sail in the background
echo "Starting Sail..."
./vendor/bin/sail up -d

# Start Vite in the background
echo "Starting Vite..."
npm run dev &
VITE_PID=$!

# Wait for the containers to be ready (adjust sleep time as needed)
echo "Waiting for containers to start..."
sleep 10

# Check if Sail is running
if ! ./vendor/bin/sail ps | grep -q "laravel.test"; then
    echo "Error: Sail containers are not running. Exiting."
    kill $VITE_PID
    exit 1
fi

# Start the queue worker. Use `queue:listen` for development as it listens for
# code changes. It's much slower, so not recommended for production.
echo "Starting queue worker..."
./vendor/bin/sail debug queue:listen --timeout=600

# Keep the script running
while true; do
    sleep 1
done