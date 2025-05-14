#!/bin/bash
# use `chmod +x run.sh` to make executable

# Function to kill process on port if active
kill_port() {
    local PORT=$1
    PID=$(lsof -ti tcp:$PORT)
    if [ -n "$PID" ]; then
        echo "Port $PORT is in use (PID $PID). Killing..."
        kill -9 $PID
    fi
}


echo "=== Checking for required software ==="

# Check for Node.js
if ! command -v node &> /dev/null; then
    echo "Node.js is not installed."
    echo "Please install it manually: https://nodejs.org/"
    exit 1
else
    echo "Node.js found: $(node -v)"
fi

# Check for npm
if ! command -v npm &> /dev/null; then
    echo "npm is not installed."
    echo "Please install it manually (comes with Node.js)."
    exit 1
else
    echo "npm found: $(npm -v)"
fi

# Check for PHP
if ! command -v php &> /dev/null; then
    echo "PHP is not installed."
    echo "Please install it: https://www.php.net/manual/en/install.php"
    exit 1
else
    echo "PHP found: $(php -v | head -n 1)"
fi

echo "=== Killing any existing servers ==="
kill_port 3000  # Node.js default
kill_port 8000  # PHP default

echo "=== Installing Node dependencies if missing ==="

# Create package.json if missing
if [ ! -f package.json ]; then
    echo "Generating package.json..."
    npm init -y
fi

# Install express and cors
npm install express cors axios

echo "Node dependencies installed."

echo "=== Starting servers ==="

# Start Node.js server
echo "Starting Node.js server in background..."
node server.js > node.log 2>&1 &
NODE_PID=$!

# Start PHP server
echo "Starting PHP server at http://localhost:8000"
cd src/private/
php -S localhost:8000 > php.log 2>&1 &
PHP_PID=$!

echo "All servers are up."
echo "Servers started: PHP=$PHP_PID, Node=$NODE_PID"
echo "Node.js: http://localhost:3000"
echo "PHP: http://localhost:8000/api.php"


# Trap Ctrl+C and shutdown everything cleanly
trap 'echo "Stopping servers..."; kill $NODE_PID $PHP_PID; wait; exit' SIGINT SIGTERM

# Wait for both to keep script running
wait