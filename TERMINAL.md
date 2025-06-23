# Terminal Implementation Guide

The Laravel Server Manager provides multiple terminal interface options to suit different needs and infrastructure requirements.

## Current Implementation: Dual Terminal Mode

The package now supports both **Simple** and **Full (Wetty)** terminal modes that can be selected before starting a session.

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

### Full Terminal Mode (Wetty Integration)

**Best for**: Full terminal functionality with interactive programs

#### Features:
- ✅ Real-time terminal emulation via WebSockets
- ✅ Interactive programs (`top`, `htop`, `nano`, `vim`)
- ✅ Full keyboard support (all special keys)
- ✅ Copy/paste functionality
- ✅ Terminal resizing
- ✅ Multiple concurrent sessions
- ✅ Complete PTY emulation

#### Prerequisites:
Install wetty globally via npm:
```bash
npm install -g wetty
```

#### Usage:
1. Select "Full (Wetty)" mode
2. Click "Start Wetty Terminal"
3. Use full terminal functionality in iframe
4. Automatically manages wetty instances

#### Automatic Instance Management:
- Laravel automatically starts/stops wetty instances
- Each server gets a unique port assignment
- Instances are cleaned up when sessions end
- Configurable limits and timeouts

## Configuration

### Wetty Configuration
Update your `config/server-manager.php`:

```php
'wetty' => [
    'path' => 'wetty', // wetty executable path
    'base_port' => 3000, // starting port for wetty instances
    'max_instances' => 10, // maximum concurrent wetty instances
    'instance_timeout' => 7200, // 2 hours in seconds
    'auto_cleanup' => true, // automatically cleanup dead instances
    'host' => '127.0.0.1', // bind to localhost only for security
    'ssl' => false, // enable SSL (requires SSL certificates)
    'ssl_cert' => null, // path to SSL certificate
    'ssl_key' => null, // path to SSL private key
],
```

### Terminal Mode Selection
Set default mode in config:
```php
'terminal' => [
    'default_mode' => 'simple', // 'simple' or 'wetty'
    // ... other terminal settings
],
```

## Installation & Setup

### 1. Install Wetty (Required for Full Mode)
```bash
# Install wetty globally
npm install -g wetty

# Verify installation
wetty --version
```

### 2. Check Installation Status
Use the "Check Wetty Status" button in the UI to verify wetty is properly installed.

### 3. Security Considerations
- Wetty instances bind to `127.0.0.1` by default for security
- Configure SSL certificates for production use
- Set appropriate instance limits and timeouts
- Use firewall rules to restrict access

## API Endpoints

### Wetty Management Routes
```php
POST /server-manager/terminal/create  // Create session (mode: simple|wetty)
POST /server-manager/terminal/wetty/stop      // Stop wetty instance
GET  /server-manager/terminal/wetty/status    // Check wetty installation
GET  /server-manager/terminal/wetty/instances // List active instances
POST /server-manager/terminal/wetty/cleanup   // Cleanup dead instances
```

## Choosing the Right Mode

| Feature | Simple Mode | Wetty Mode |
|---------|-------------|------------|
| Setup Complexity | ⭐ Easy | ⭐⭐ Medium |
| Prerequisites | None | Node.js + wetty |
| Interactive Programs | ❌ | ✅ |
| Real-time Communication | ❌ | ✅ (WebSocket) |
| Command Execution | ✅ | ✅ |
| Copy/Paste | ⚠️ Limited | ✅ Full |
| Keyboard Support | ⭐ Basic | ⭐⭐⭐ Complete |
| Resource Usage | ⭐ Low | ⭐⭐ Medium |
| Production Ready | ✅ | ✅ |

## Troubleshooting

### Wetty Not Starting
1. Check if wetty is installed: `wetty --version`
2. Verify port availability (default: 3000+)
3. Check Laravel logs for error details
4. Ensure proper permissions for wetty executable

### Connection Issues
1. Verify server SSH credentials are correct
2. Check firewall settings
3. Ensure SSH key permissions are correct (600)
4. Test SSH connection manually

### Performance Issues
1. Reduce `max_instances` in config
2. Decrease `instance_timeout`
3. Enable `auto_cleanup`
4. Monitor system resources

## Migration from Previous Versions

If upgrading from the previous WebSSH2 documentation:

1. **Wetty replaces WebSSH2** - More modern and actively maintained
2. **Automatic management** - No manual Docker setup required
3. **Integrated installation** - Uses npm instead of Docker
4. **Better security** - Localhost binding by default

## Recommendation

- **Start with Simple Mode** - Works immediately for basic tasks
- **Use Wetty Mode** - When you need interactive programs or full terminal features
- **Proper Installation** - Follow wetty installation steps for production use

The current implementation provides **immediate functionality** with Simple mode while offering **complete terminal capabilities** through integrated Wetty management.