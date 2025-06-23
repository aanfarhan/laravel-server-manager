<?php

namespace ServerManager\LaravelServerManager\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Server extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'host',
        'port',
        'username',
        'password',
        'private_key',
        'private_key_password',
        'metadata',
        'status',
        'last_connected_at',
        'last_error'
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_connected_at' => 'datetime',
        'port' => 'integer'
    ];

    protected $attributes = [
        'port' => 22,
        'status' => 'disconnected'
    ];

    protected $hidden = [
        'password',
        'private_key',
        'private_key_password'
    ];


    public function monitoringLogs(): HasMany
    {
        return $this->hasMany(MonitoringLog::class);
    }

    public function getPasswordAttribute($value)
    {
        if (config('server-manager.security.encrypt_credentials', true) && $value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value; // Return as-is if decryption fails (for backward compatibility)
            }
        }
        return $value;
    }

    public function setPasswordAttribute($value)
    {
        // Don't encrypt null values or already encrypted values
        if ($value === null) {
            $this->attributes['password'] = null;
            return;
        }
        
        if (config('server-manager.security.encrypt_credentials', true) && $value) {
            // Check if value is already encrypted (to prevent double encryption)
            if (is_string($value) && str_contains($value, '"iv":')) {
                $this->attributes['password'] = $value;
            } else {
                $this->attributes['password'] = Crypt::encryptString($value);
            }
        } else {
            $this->attributes['password'] = $value;
        }
    }

    public function getPrivateKeyAttribute($value)
    {
        if (config('server-manager.security.encrypt_credentials', true) && $value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }
        return $value;
    }

    public function setPrivateKeyAttribute($value)
    {
        if (config('server-manager.security.encrypt_credentials', true) && $value) {
            $this->attributes['private_key'] = Crypt::encryptString($value);
        } else {
            $this->attributes['private_key'] = $value;
        }
    }

    public function getPrivateKeyPasswordAttribute($value)
    {
        if (config('server-manager.security.encrypt_credentials', true) && $value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }
        return $value;
    }

    public function setPrivateKeyPasswordAttribute($value)
    {
        if (config('server-manager.security.encrypt_credentials', true) && $value) {
            $this->attributes['private_key_password'] = Crypt::encryptString($value);
        } else {
            $this->attributes['private_key_password'] = $value;
        }
    }

    public function getSshConfig(): array
    {
        $config = [
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
        ];

        if ($this->private_key) {
            $config['private_key'] = $this->private_key;
            if ($this->private_key_password) {
                $config['private_key_password'] = $this->private_key_password;
            }
        } else {
            $config['password'] = $this->password;
        }

        return $config;
    }

    public function updateConnectionStatus(string $status, ?string $error = null): void
    {
        $this->update([
            'status' => $status,
            'last_connected_at' => $status === 'connected' ? now() : $this->last_connected_at,
            'last_error' => $error
        ]);
    }

    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    public function hasError(): bool
    {
        return $this->status === 'error' && !empty($this->last_error);
    }

    public function getConnectionTypeAttribute(): string
    {
        return $this->private_key ? 'key' : 'password';
    }

    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'disabled');
    }

    public function scopeConnected($query)
    {
        return $query->where('status', 'connected');
    }

    public function scopeWithErrors($query)
    {
        return $query->where('status', 'error')->whereNotNull('last_error');
    }
}