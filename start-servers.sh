#!/bin/bash

# Start PHP server in background
cd backend
php -S localhost:8001 start-server.php > php.log 2>&1 &
echo "PHP Server started on port 8001"

# Start frontend server
cd ../frontend
npm run dev
