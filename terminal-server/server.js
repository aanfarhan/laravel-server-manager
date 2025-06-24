#!/usr/bin/env node

const WebSocket = require('ws');
const { Client } = require('ssh2');
const jwt = require('jsonwebtoken');
const { v4: uuidv4 } = require('uuid');
require('dotenv').config();

class TerminalServer {
    constructor(options = {}) {
        this.port = options.port || process.env.PORT || 3001;
        this.jwtSecret = process.env.JWT_SECRET || 'default-secret-change-in-production';
        this.maxConnections = parseInt(process.env.MAX_CONNECTIONS) || 100;
        this.connectionTimeout = parseInt(process.env.CONNECTION_TIMEOUT) || 300000; // 5 minutes
        this.authTimeout = parseInt(process.env.AUTH_TIMEOUT) || 30000; // 30 seconds
        
        this.connections = new Map();
        this.server = null;
        
        this.setupSignalHandlers();
    }

    start() {
        // Create HTTP server first
        const http = require('http');
        this.httpServer = http.createServer((req, res) => {
            if (req.url === '/health' || req.url === '/') {
                const stats = this.getStats();
                res.writeHead(200, { 
                    'Content-Type': 'application/json',
                    'Access-Control-Allow-Origin': '*'
                });
                res.end(JSON.stringify({
                    status: 'ok',
                    message: 'Terminal WebSocket server is running',
                    timestamp: new Date().toISOString(),
                    ...stats
                }));
            } else {
                res.writeHead(404, { 'Content-Type': 'text/plain' });
                res.end('Not Found');
            }
        });

        // Create WebSocket server using the HTTP server
        this.server = new WebSocket.Server({ 
            server: this.httpServer,
            maxPayload: 64 * 1024 // 64KB max message size
        });

        // Start the HTTP server
        this.httpServer.listen(this.port, () => {
            console.log(`HTTP server listening on port ${this.port}`);
            console.log(`Health check endpoint available at http://localhost:${this.port}/health`);
        });

        this.server.on('connection', (ws, req) => {
            this.handleConnection(ws, req);
        });

        this.server.on('error', (error) => {
            console.error('WebSocket server error:', error);
        });

        // Cleanup interval - remove dead connections every minute
        setInterval(() => {
            this.cleanupConnections();
        }, 60000);

        console.log(`Terminal WebSocket server listening on port ${this.port}`);
        console.log(`Max connections: ${this.maxConnections}`);
        console.log(`Connection timeout: ${this.connectionTimeout}ms`);
    }

    stop() {
        console.log('Shutting down terminal server...');
        
        // Close all connections
        this.connections.forEach((conn) => {
            this.closeConnection(conn.id);
        });

        if (this.server) {
            this.server.close(() => {
                if (this.httpServer) {
                    this.httpServer.close(() => {
                        console.log('Terminal server shutdown complete');
                        process.exit(0);
                    });
                } else {
                    console.log('Terminal server shutdown complete');
                    process.exit(0);
                }
            });
        }
    }

    handleConnection(ws, req) {
        const connectionId = uuidv4();
        const clientIp = req.socket.remoteAddress;
        
        console.log(`New connection: ${connectionId} from ${clientIp}`);

        // Check connection limits
        if (this.connections.size >= this.maxConnections) {
            console.log(`Connection limit reached, rejecting ${connectionId}`);
            ws.close(1008, 'Server at capacity');
            return;
        }

        const connection = {
            id: connectionId,
            ws: ws,
            clientIp: clientIp,
            authenticated: false,
            sshClient: null,
            stream: null,
            createdAt: Date.now(),
            lastActivity: Date.now(),
            serverInfo: null
        };

        this.connections.set(connectionId, connection);

        // Set authentication timeout
        const authTimeout = setTimeout(() => {
            if (!connection.authenticated) {
                console.log(`Authentication timeout for connection ${connectionId}`);
                ws.close(1008, 'Authentication timeout');
            }
        }, this.authTimeout);

        // Set connection timeout
        const connectionTimeout = setTimeout(() => {
            console.log(`Connection timeout for ${connectionId}`);
            this.closeConnection(connectionId);
        }, this.connectionTimeout);

        ws.on('message', (data) => {
            try {
                connection.lastActivity = Date.now();
                const message = JSON.parse(data.toString());
                this.handleMessage(connectionId, message);
            } catch (error) {
                console.error(`Message parsing error for ${connectionId}:`, error);
                this.sendError(connectionId, 'Invalid message format');
            }
        });

        ws.on('close', (code, reason) => {
            console.log(`Connection ${connectionId} closed: ${code} ${reason}`);
            clearTimeout(authTimeout);
            clearTimeout(connectionTimeout);
            this.closeConnection(connectionId);
        });

        ws.on('error', (error) => {
            console.error(`WebSocket error for ${connectionId}:`, error);
            this.closeConnection(connectionId);
        });

        // Send connection acknowledgment
        this.sendMessage(connectionId, {
            type: 'connected',
            connectionId: connectionId,
            message: 'Terminal server connected. Please authenticate.'
        });
    }

    handleMessage(connectionId, message) {
        const connection = this.connections.get(connectionId);
        if (!connection) return;

        switch (message.type) {
            case 'auth':
                this.handleAuth(connectionId, message);
                break;
            case 'connect':
                this.handleConnect(connectionId, message);
                break;
            case 'input':
                this.handleInput(connectionId, message);
                break;
            case 'resize':
                this.handleResize(connectionId, message);
                break;
            case 'ping':
                this.sendMessage(connectionId, { type: 'pong' });
                break;
            default:
                this.sendError(connectionId, `Unknown message type: ${message.type}`);
        }
    }

    handleAuth(connectionId, message) {
        const connection = this.connections.get(connectionId);
        if (!connection) return;

        try {
            const decoded = jwt.verify(message.token, this.jwtSecret);
            
            // Validate token payload
            if (!decoded.server_id || !decoded.host || !decoded.username) {
                throw new Error('Invalid token payload');
            }

            connection.authenticated = true;
            connection.serverInfo = decoded;
            
            console.log(`Connection ${connectionId} authenticated for server ${decoded.server_id}`);
            
            this.sendMessage(connectionId, {
                type: 'auth_success',
                message: 'Authentication successful'
            });
            
        } catch (error) {
            console.error(`Authentication failed for ${connectionId}:`, error.message);
            this.sendError(connectionId, 'Authentication failed');
            connection.ws.close(1008, 'Authentication failed');
        }
    }

    handleConnect(connectionId, message) {
        const connection = this.connections.get(connectionId);
        if (!connection || !connection.authenticated) {
            this.sendError(connectionId, 'Not authenticated');
            return;
        }

        if (connection.sshClient) {
            this.sendError(connectionId, 'Already connected to SSH');
            return;
        }

        const { host, port, username, password, privateKey } = connection.serverInfo;
        
        console.log(`Establishing SSH connection for ${connectionId} to ${username}@${host}:${port}`);

        connection.sshClient = new Client();
        
        connection.sshClient.on('ready', () => {
            console.log(`SSH connection ready for ${connectionId}`);
            
            connection.sshClient.shell({
                rows: message.rows || parseInt(process.env.DEFAULT_ROWS) || 24,
                cols: message.cols || parseInt(process.env.DEFAULT_COLS) || 80,
                term: 'xterm-256color'
            }, (err, stream) => {
                if (err) {
                    console.error(`Shell creation failed for ${connectionId}:`, err);
                    this.sendError(connectionId, `Failed to create shell: ${err.message}`);
                    return;
                }

                connection.stream = stream;

                stream.on('data', (data) => {
                    this.sendMessage(connectionId, {
                        type: 'data',
                        data: data.toString('utf8')
                    });
                });

                stream.on('close', () => {
                    console.log(`SSH stream closed for ${connectionId}`);
                    this.sendMessage(connectionId, {
                        type: 'disconnected',
                        message: 'SSH session ended'
                    });
                });

                stream.stderr.on('data', (data) => {
                    this.sendMessage(connectionId, {
                        type: 'data',
                        data: data.toString('utf8')
                    });
                });

                this.sendMessage(connectionId, {
                    type: 'ready',
                    message: 'SSH connection established'
                });
            });
        });

        connection.sshClient.on('error', (error) => {
            console.error(`SSH connection error for ${connectionId}:`, error);
            this.sendError(connectionId, `SSH connection failed: ${error.message}`);
        });

        connection.sshClient.on('end', () => {
            console.log(`SSH connection ended for ${connectionId}`);
        });

        // Connect using provided credentials
        const sshConfig = {
            host: host,
            port: port || 22,
            username: username,
            readyTimeout: parseInt(process.env.SSH_TIMEOUT) || 10000,
            keepaliveInterval: parseInt(process.env.SSH_KEEPALIVE_INTERVAL) || 30000
        };

        if (privateKey) {
            sshConfig.privateKey = privateKey;
        } else if (password) {
            sshConfig.password = password;
        } else {
            this.sendError(connectionId, 'No authentication method provided');
            return;
        }

        connection.sshClient.connect(sshConfig);
    }

    handleInput(connectionId, message) {
        const connection = this.connections.get(connectionId);
        if (!connection || !connection.stream) {
            this.sendError(connectionId, 'No active SSH session');
            return;
        }

        connection.stream.write(message.data);
    }

    handleResize(connectionId, message) {
        const connection = this.connections.get(connectionId);
        if (!connection || !connection.stream) {
            return;
        }

        const rows = Math.min(Math.max(1, parseInt(message.rows)), parseInt(process.env.MAX_ROWS) || 200);
        const cols = Math.min(Math.max(1, parseInt(message.cols)), parseInt(process.env.MAX_COLS) || 500);

        connection.stream.setWindow(rows, cols);
    }

    sendMessage(connectionId, message) {
        const connection = this.connections.get(connectionId);
        if (!connection || connection.ws.readyState !== WebSocket.OPEN) {
            return;
        }

        try {
            connection.ws.send(JSON.stringify(message));
        } catch (error) {
            console.error(`Failed to send message to ${connectionId}:`, error);
            this.closeConnection(connectionId);
        }
    }

    sendError(connectionId, errorMessage) {
        this.sendMessage(connectionId, {
            type: 'error',
            message: errorMessage
        });
    }

    closeConnection(connectionId) {
        const connection = this.connections.get(connectionId);
        if (!connection) return;

        console.log(`Closing connection ${connectionId}`);

        // Close SSH stream
        if (connection.stream) {
            connection.stream.end();
        }

        // Close SSH client
        if (connection.sshClient) {
            connection.sshClient.end();
        }

        // Close WebSocket
        if (connection.ws && connection.ws.readyState === WebSocket.OPEN) {
            connection.ws.close();
        }

        this.connections.delete(connectionId);
    }

    cleanupConnections() {
        const now = Date.now();
        const staleConnections = [];

        this.connections.forEach((connection, connectionId) => {
            // Close connections that have been inactive for too long
            if (now - connection.lastActivity > this.connectionTimeout) {
                staleConnections.push(connectionId);
            }
        });

        staleConnections.forEach(connectionId => {
            console.log(`Cleaning up stale connection ${connectionId}`);
            this.closeConnection(connectionId);
        });

        if (staleConnections.length > 0) {
            console.log(`Cleaned up ${staleConnections.length} stale connections`);
        }
    }


    setupSignalHandlers() {
        process.on('SIGTERM', () => {
            console.log('Received SIGTERM, shutting down gracefully');
            this.stop();
        });

        process.on('SIGINT', () => {
            console.log('Received SIGINT, shutting down gracefully');
            this.stop();
        });

        process.on('uncaughtException', (error) => {
            console.error('Uncaught exception:', error);
            this.stop();
        });

        process.on('unhandledRejection', (reason, promise) => {
            console.error('Unhandled rejection at:', promise, 'reason:', reason);
        });
    }

    getStats() {
        return {
            totalConnections: this.connections.size,
            authenticatedConnections: Array.from(this.connections.values()).filter(c => c.authenticated).length,
            activeSSHSessions: Array.from(this.connections.values()).filter(c => c.stream).length,
            uptime: process.uptime(),
            memoryUsage: process.memoryUsage()
        };
    }
}

// Start server if this file is run directly
if (require.main === module) {
    const server = new TerminalServer();
    server.start();
}

module.exports = TerminalServer;