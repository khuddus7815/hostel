#!/bin/bash

# Kill any existing PHP servers
pkill -f "php -S localhost"

# Start PHP server in background
cd backend
php -S localhost:8002 start-server.php > php.log 2>&1 &
echo "PHP Server started on port 8002"

# Start frontend server
cd ../frontend
npm run dev
