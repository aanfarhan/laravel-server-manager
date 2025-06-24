@echo off
setlocal EnableDelayedExpansion

REM Laravel Server Manager - Terminal Server Installation Script (Windows)
REM This script installs and configures the WebSocket Terminal Server

echo.
echo 🚀 Laravel Server Manager - Terminal Server Setup
echo =================================================
echo.

REM Check if Node.js is installed
where node >nul 2>nul
if %errorlevel% neq 0 (
    echo ❌ Node.js is not installed. Please install Node.js 16+ first:
    echo    https://nodejs.org/
    pause
    exit /b 1
)

REM Get Node.js version
for /f "tokens=*" %%i in ('node --version') do set NODE_VERSION=%%i
echo ✅ Node.js version %NODE_VERSION% detected

REM Check if npm is available
where npm >nul 2>nul
if %errorlevel% neq 0 (
    echo ❌ npm is not installed. Please install npm first.
    pause
    exit /b 1
)

echo ✅ npm detected

REM Install dependencies
echo.
echo 📦 Installing dependencies...
call npm install

if %errorlevel% neq 0 (
    echo ❌ Failed to install dependencies
    pause
    exit /b 1
)

echo ✅ Dependencies installed successfully

REM Copy environment file if it doesn't exist
if not exist .env (
    echo.
    echo 📝 Creating environment configuration...
    copy .env.example .env >nul
    echo ✅ Environment file created (.env)
    echo ⚠️  Please edit .env file with your configuration before starting the server
) else (
    echo ℹ️  Environment file already exists
)

echo.
echo 🎉 Installation completed successfully!
echo.
echo Next steps:
echo 1. Edit the .env file with your configuration:
echo    - Set JWT_SECRET to match your Laravel APP_KEY
echo    - Configure PORT (default: 3001)
echo    - Adjust other settings as needed
echo.
echo 2. Start the server:
echo    npm start          # Production
echo    npm run dev        # Development (with auto-restart)
echo.
echo 3. Update your Laravel .env file:
echo    WEBSOCKET_TERMINAL_HOST=localhost
echo    WEBSOCKET_TERMINAL_PORT=3001
echo    WEBSOCKET_TERMINAL_JWT_SECRET=%%APP_KEY%%
echo.
echo 4. For production deployment, consider using PM2:
echo    npm install -g pm2
echo    pm2 start server.js --name terminal-server
echo.
echo 📚 See README.md for detailed configuration and usage instructions
echo.
pause