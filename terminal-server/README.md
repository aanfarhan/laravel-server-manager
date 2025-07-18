# Laravel Server Manager - Terminal WebSocket Server

A standalone Node.js WebSocket server that provides real-time terminal functionality for the Laravel Server Manager package.

## Features

- **WebSocket-based terminal** with real-time communication
- **JWT authentication** for secure connections
- **SSH connection management** with automatic cleanup
- **Terminal resizing** and full keyboard support
- **Connection limits** and timeout management
- **Graceful shutdown** with cleanup
- **Performance monitoring** and logging

## Installation

### Quick Setup
```bash
cd terminal-server
chmod +x install.sh
./install.sh          # Linux/macOS
# OR
install.bat            # Windows
```

### Manual Setup
```bash
cd terminal-server
npm install
cp .env.example .env
```

### Verify Installation
```bash
npm run check
```

## Configuration

Copy the environment file and configure:

```bash
cp .env.example .env
```

Edit `.env` with your settings:

```env
PORT=3001
JWT_SECRET=your-secure-jwt-secret-here
MAX_CONNECTIONS=100
CONNECTION_TIMEOUT=300000
```

## Usage

### Development
```bash
npm run dev
```

### Production
```bash
npm start
```

## WebSocket Protocol

### 1. Connection
Client connects to `ws://localhost:3001`

### 2. Authentication
```json
{
  "type": "auth",
  "token": "jwt-token-from-laravel"
}
```

### 3. SSH Connection
```json
{
  "type": "connect",
  "rows": 24,
  "cols": 80
}
```

### 4. Input
```json
{
  "type": "input",
  "data": "ls -la\r"
}
```

### 5. Resize
```json
{
  "type": "resize",
  "rows": 30,
  "cols": 100
}
```

## Message Types

### From Client to Server:
- `auth` - Authenticate with JWT token
- `connect` - Establish SSH connection
- `input` - Send input to terminal
- `resize` - Resize terminal
- `ping` - Keep connection alive

### From Server to Client:
- `connected` - Connection established
- `auth_success` - Authentication successful
- `ready` - SSH connection ready
- `data` - Terminal output data
- `error` - Error message
- `disconnected` - SSH session ended
- `pong` - Ping response

## Security Features

- **JWT token authentication** - Validates Laravel-generated tokens
- **Connection limits** - Prevents resource exhaustion
- **Timeout management** - Automatically closes stale connections
- **Input validation** - Sanitizes all incoming messages
- **Error handling** - Graceful error recovery

## Production Deployment

### PM2 (Recommended)
```bash
npm install -g pm2
pm2 start server.js --name "terminal-server"
pm2 startup
pm2 save
```

### Docker
```dockerfile
FROM node:18-alpine
WORKDIR /app
COPY package*.json ./
RUN npm ci --only=production
COPY . .
EXPOSE 3001
CMD ["node", "server.js"]
```

### Nginx Proxy
```nginx
location /terminal-ws {
    proxy_pass http://localhost:3001;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
}
```

## Monitoring

The server provides stats endpoint for monitoring:

```javascript
// Get server statistics
const stats = server.getStats();
console.log(stats);
// {
//   totalConnections: 5,
//   authenticatedConnections: 4,
//   activeSSHSessions: 3,
//   uptime: 3600,
//   memoryUsage: {...}
// }
```

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `PORT` | 3001 | WebSocket server port |
| `JWT_SECRET` | required | JWT signing secret |
| `MAX_CONNECTIONS` | 100 | Maximum concurrent connections |
| `CONNECTION_TIMEOUT` | 300000 | Connection timeout (ms) |
| `AUTH_TIMEOUT` | 30000 | Authentication timeout (ms) |
| `DEFAULT_ROWS` | 24 | Default terminal rows |
| `DEFAULT_COLS` | 80 | Default terminal columns |
| `SSH_TIMEOUT` | 10000 | SSH connection timeout (ms) |
| `SSH_KEEPALIVE_INTERVAL` | 30000 | SSH keepalive interval (ms) |

## Troubleshooting

### "Cannot find module" Error
This error occurs when Node.js dependencies are not installed. Follow these steps:

1. **Check if node_modules exists:**
   ```bash
   ls -la node_modules/
   ```

2. **Install dependencies:**
   ```bash
   npm install
   # or with verbose output
   npm install --verbose
   ```

3. **Clear npm cache if needed:**
   ```bash
   npm cache clean --force
   rm -rf node_modules package-lock.json
   npm install
   ```

4. **Check Node.js version:**
   ```bash
   node --version  # Should be 16.0.0 or higher
   npm --version
   ```

5. **Alternative: Use yarn:**
   ```bash
   yarn install
   ```

### Connection Issues
1. Check if server is running: `curl http://localhost:3001`
2. Verify JWT secret matches Laravel configuration
3. Check firewall settings
4. Ensure port 3001 is not in use by another service

### SSH Connection Failures
1. Verify SSH credentials in Laravel
2. Test SSH connection manually: `ssh user@host`
3. Check SSH server configuration
4. Verify private key permissions (600)

### Performance Issues
1. Monitor connection count
2. Adjust timeout settings in .env
3. Check memory usage: `npm run stats`
4. Review error logs

### Installation Issues
1. **Permission errors:**
   ```bash
   sudo chown -R $(whoami) ~/.npm
   ```

2. **Missing build tools (Linux):**
   ```bash
   sudo apt-get install build-essential
   ```

3. **Missing build tools (Windows):**
   ```bash
   npm install --global windows-build-tools
   ```

4. **Node.js version conflicts:**
   - Use nvm to manage Node.js versions
   - Ensure you're using Node.js 16+

## Development

### Running Tests
```bash
npm test
```

### Code Structure
- `server.js` - Main server implementation
- `package.json` - Dependencies and scripts
- `.env.example` - Configuration template
- `README.md` - This documentation