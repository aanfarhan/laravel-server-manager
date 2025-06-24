# Terminal Implementation Guide

The Laravel Server Manager provides multiple terminal interface options to suit different needs and infrastructure requirements.

## Current Implementation: Dual Terminal Mode

The package now supports both **Simple** and **Full (WebSocket)** terminal modes that can be selected before starting a session.

### Simple Terminal Mode (Default)

**Best for**: Basic command execution and server administration

#### Features:
- ✅ Type commands and press Enter to execute
- ✅ See command output immediately  
- ✅ Command history display
- ✅ Works with all special characters, spaces, and complex commands
- ✅ No additional infrastructure required
- ✅ Directory navigation with `cd` command persistence
- ⚠️ No real-time interactive programs (like `top`, `nano`)
- ⚠️ Each command executes in a fresh context

#### Usage:
1. Select "Simple" mode (default)
2. Click "Start Simple Terminal" 
3. Type commands in the input field
4. Press Enter to execute
5. View output in the terminal window

### Full Terminal Mode (WebSocket Integration)

**Best for**: Full terminal functionality with interactive programs

#### Features:
- ✅ Real-time terminal emulation via WebSockets + xterm.js
- ✅ Interactive programs (`top`, `htop`, `nano`, `vim`)
- ✅ Full keyboard support (all special keys)
- ✅ Copy/paste functionality
- ✅ Terminal resizing
- ✅ Multiple concurrent sessions
- ✅ Complete PTY emulation with SSH2
- ✅ JWT-based authentication
- ✅ Automatic session management

#### Prerequisites:
Install Node.js dependencies and start the WebSocket server:
```bash
cd vendor/omniglies/laravel-server-manager/terminal-server
npm install
```

#### Usage:
1. Start the WebSocket terminal server (see below)
2. Select "Full (WebSocket)" mode in the UI
3. Click "Start WebSocket Terminal"
4. Use full terminal functionality via xterm.js
5. Sessions are automatically authenticated and managed

#### WebSocket Server Management:
- Standalone Node.js server with WebSocket communication
- JWT-based authentication for security
- Automatic connection cleanup and session management
- Configurable connection limits and timeouts
- Production-ready with PM2 support

## Configuration

### WebSocket Terminal Configuration
Update your `config/server-manager.php`:

```php
'websocket' => [
    'host' => env('WEBSOCKET_TERMINAL_HOST', 'localhost'),
    'port' => env('WEBSOCKET_TERMINAL_PORT', 3001),
    'ssl' => env('WEBSOCKET_TERMINAL_SSL', false),
    'jwt_secret' => env('WEBSOCKET_TERMINAL_JWT_SECRET', env('APP_KEY')),
    'token_ttl' => env('WEBSOCKET_TERMINAL_TOKEN_TTL', 3600), // 1 hour
    'server_path' => env('WEBSOCKET_TERMINAL_SERVER_PATH', base_path('terminal-server/server.js')),
    'auto_start' => env('WEBSOCKET_TERMINAL_AUTO_START', false),
    'max_connections' => env('WEBSOCKET_TERMINAL_MAX_CONNECTIONS', 100),
    'connection_timeout' => env('WEBSOCKET_TERMINAL_CONNECTION_TIMEOUT', 300000), // 5 minutes
],
```

### Environment Variables
Add to your `.env` file:

```env
# WebSocket Terminal Server
WEBSOCKET_TERMINAL_HOST=localhost
WEBSOCKET_TERMINAL_PORT=3001
WEBSOCKET_TERMINAL_SSL=false
WEBSOCKET_TERMINAL_JWT_SECRET=your-jwt-secret-here
WEBSOCKET_TERMINAL_TOKEN_TTL=3600
WEBSOCKET_TERMINAL_MAX_CONNECTIONS=100
```

### Terminal Mode Selection
Set default mode in config:
```php
'terminal' => [
    'default_mode' => 'simple', // 'simple' or 'websocket'
    // ... other terminal settings
],
```

## Installation & Setup

### 1. Install WebSocket Terminal Server
```bash
# Navigate to the terminal server directory
cd vendor/omniglies/laravel-server-manager/terminal-server

# Install Node.js dependencies
npm install

# Copy environment configuration
cp .env.example .env
```

### 2. Configure WebSocket Server
Edit `terminal-server/.env`:

```env
PORT=3001
JWT_SECRET=your-jwt-secret-matching-laravel
MAX_CONNECTIONS=100
CONNECTION_TIMEOUT=300000
```

### 3. Start WebSocket Server

#### Development
```bash
npm run dev
```

#### Production
```bash
# Direct start
npm start

# With PM2 (recommended)
pm2 start server.js --name "terminal-server"
pm2 startup
pm2 save
```

#### Docker Deployment
```bash
# Build and run with Docker
docker build -t laravel-terminal-server .
docker run -d -p 3001:3001 --name terminal-server laravel-terminal-server
```

### 4. Verify Installation
Use the "Check Server Status" button in the UI to verify the WebSocket server is running.

### 5. Security Considerations
- WebSocket server binds to localhost by default for security
- JWT tokens are used for authentication between Laravel and WebSocket server
- Configure SSL certificates for production use
- Set appropriate connection limits and timeouts
- Use reverse proxy (nginx) for SSL termination

## API Endpoints

### WebSocket Terminal Management Routes
```php
POST /server-manager/terminal/create                    // Create session (mode: simple|websocket)
POST /server-manager/terminal/websocket/token           // Generate WebSocket authentication token
POST /server-manager/terminal/websocket/revoke          // Revoke WebSocket token
GET  /server-manager/terminal/websocket/status          // Check WebSocket server status
GET  /server-manager/terminal/websocket/tokens          // List active tokens
POST /server-manager/terminal/websocket/cleanup         // Cleanup expired tokens
POST /server-manager/terminal/websocket/start-server    // Start WebSocket server
POST /server-manager/terminal/websocket/stop-server     // Stop WebSocket server
```

## Choosing the Right Mode

| Feature | Simple Mode | WebSocket Mode |
|---------|-------------|----------------|
| Setup Complexity | ⭐ Easy | ⭐⭐ Medium |
| Prerequisites | None | Node.js + WebSocket server |
| Interactive Programs | ❌ | ✅ |
| Real-time Communication | ❌ | ✅ (WebSocket) |
| Command Execution | ✅ | ✅ |
| Copy/Paste | ⚠️ Limited | ✅ Full |
| Keyboard Support | ⭐ Basic | ⭐⭐⭐ Complete |
| Resource Usage | ⭐ Low | ⭐⭐ Medium |
| Production Ready | ✅ | ✅ |
| Authentication | Session-based | JWT + WebSocket |
| Terminal Emulation | Basic | Full xterm.js |

## Troubleshooting

### WebSocket Server Not Starting
1. Check if Node.js is installed: `node --version`
2. Verify port availability: `lsof -i :3001`
3. Check server logs: `npm start` or `pm2 logs terminal-server`
4. Ensure proper permissions for server files

### Authentication Issues
1. Verify JWT secret matches between Laravel and WebSocket server
2. Check token expiration settings
3. Ensure proper CORS configuration if needed
4. Verify WebSocket URL is accessible

### Connection Issues
1. Verify server SSH credentials are correct
2. Check firewall settings for WebSocket port
3. Ensure SSH key permissions are correct (600)
4. Test SSH connection manually
5. Check WebSocket server is running: `curl http://localhost:3001`

### Performance Issues
1. Reduce `max_connections` in WebSocket server config
2. Decrease `connection_timeout`
3. Enable `auto_cleanup` for tokens
4. Monitor WebSocket server memory usage
5. Use PM2 for production process management

### Browser Issues
1. Check browser console for WebSocket errors
2. Verify xterm.js loads correctly
3. Test with different browsers
4. Clear browser cache and cookies

## Production Deployment

### Recommended Setup
1. **Process Manager**: Use PM2 for WebSocket server
2. **Reverse Proxy**: Use Nginx for SSL termination
3. **Monitoring**: Monitor WebSocket server health
4. **Logging**: Centralized logging for debugging
5. **Security**: Firewall rules and rate limiting

### Nginx Configuration
```nginx
location /terminal-ws {
    proxy_pass http://localhost:3001;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

## Recommendation

- **Start with Simple Mode** - Works immediately for basic tasks
- **Use WebSocket Mode** - When you need interactive programs or full terminal features
- **Production Setup** - Follow WebSocket server installation and PM2 setup for production

The current implementation provides **immediate functionality** with Simple mode while offering **complete terminal capabilities** through modern WebSocket + xterm.js technology.