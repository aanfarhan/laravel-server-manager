#!/bin/bash

# Laravel Server Manager - Terminal Server Installation Script
# This script installs and configures the WebSocket Terminal Server

set -e

echo "🚀 Laravel Server Manager - Terminal Server Setup"
echo "================================================="

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "❌ Node.js is not installed. Please install Node.js 16+ first:"
    echo "   https://nodejs.org/"
    exit 1
fi

# Check Node.js version
NODE_VERSION=$(node --version | cut -d'v' -f2)
REQUIRED_VERSION="16.0.0"

if ! node -p "process.exit(require('semver').gte('$NODE_VERSION', '$REQUIRED_VERSION'))" &> /dev/null; then
    echo "❌ Node.js version $NODE_VERSION detected. Required: $REQUIRED_VERSION+"
    echo "   Please upgrade Node.js: https://nodejs.org/"
    exit 1
fi

echo "✅ Node.js version $NODE_VERSION detected"

# Check if npm is available
if ! command -v npm &> /dev/null; then
    echo "❌ npm is not installed. Please install npm first."
    exit 1
fi

echo "✅ npm detected"

# Install dependencies
echo "📦 Installing dependencies..."
npm install

if [ $? -ne 0 ]; then
    echo "❌ Failed to install dependencies"
    exit 1
fi

echo "✅ Dependencies installed successfully"

# Copy environment file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating environment configuration..."
    cp .env.example .env
    echo "✅ Environment file created (.env)"
    echo "⚠️  Please edit .env file with your configuration before starting the server"
else
    echo "ℹ️  Environment file already exists"
fi

# Make the server executable
chmod +x server.js

echo ""
echo "🎉 Installation completed successfully!"
echo ""
echo "Next steps:"
echo "1. Edit the .env file with your configuration:"
echo "   - Set JWT_SECRET to match your Laravel APP_KEY"
echo "   - Configure PORT (default: 3001)"
echo "   - Adjust other settings as needed"
echo ""
echo "2. Start the server:"
echo "   npm start          # Production"
echo "   npm run dev        # Development (with auto-restart)"
echo ""
echo "3. Update your Laravel .env file:"
echo "   WEBSOCKET_TERMINAL_HOST=localhost"
echo "   WEBSOCKET_TERMINAL_PORT=3001"
echo "   WEBSOCKET_TERMINAL_JWT_SECRET=\${APP_KEY}"
echo ""
echo "4. For production deployment, consider using PM2:"
echo "   npm install -g pm2"
echo "   pm2 start server.js --name terminal-server"
echo ""
echo "📚 See README.md for detailed configuration and usage instructions"