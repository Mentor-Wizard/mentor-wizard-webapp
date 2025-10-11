# FrankenPHP + Caddy Migration Guide

## Overview

Your Laravel application has been successfully migrated from Nginx to
**FrankenPHP** with **Caddy** as the web server. This setup provides:

- **FrankenPHP**: A modern PHP application server built on Go with native
  Laravel support
- **Caddy**: An automatic HTTPS web server with built-in SSL certificate
  management
- **Alpine Linux**: Lightweight, secure base images
- **Xdebug**: Full debugging and coverage support in development

## What Changed

### Docker Images

- **Production**: `dunglas/frankenphp:1.3.6-php8.4-alpine`
- **Development**: `dunglas/frankenphp:1.3.6-php8.4-alpine` with Xdebug
  extension
- **Base OS**: Alpine Linux (lightweight and secure)

### Architecture Changes

- ✅ Removed Nginx configuration files
- ✅ Added Caddy configuration with automatic SSL
- ✅ FrankenPHP handles PHP execution natively (no php-fpm)
- ✅ Integrated Laravel Octane support
- ✅ Preserved Xdebug support for development
- ✅ Maintained existing Docker Compose networks

### File Structure

```
docker/
├── caddy/
│   └── Caddyfile           # Caddy configuration with SSL support
├── php/
│   ├── Dockerfile          # Production FrankenPHP image
│   ├── Dockerfile-dev      # Development FrankenPHP image with Xdebug
│   └── supervisord/
│       └── supervisord.conf # Process management
└── nginx/                  # ⚠️ Legacy - can be removed
```

## Current Status

### ✅ Working Features

- **HTTP/HTTPS**: Automatic HTTPS with self-signed certificates for localhost
- **Laravel Integration**: Native FrankenPHP + Laravel support
- **Xdebug**: Fully functional for debugging and coverage
- **Static Assets**: Optimized serving with proper caching headers
- **Process Management**: Supervisord for reliable service management
- **Health Checks**: `/up` endpoint working correctly

### 🔧 Current Configuration

- **HTTP**: Redirects to HTTPS (308 status)
- **HTTPS**: Working on port 443 with automatic certificates
- **Development**: Xdebug enabled with coverage support
- **SSL**: Automatic certificate generation by Caddy

## Usage Instructions

### Development Environment

1. **Start the application**:

    ```bash
    docker compose up app db redis --detach
    ```

2. **Access the application**:
    - **HTTPS** (recommended): https://localhost (may show certificate warning -
      this is normal for self-signed certificates)
    - **HTTP**: http://localhost (will redirect to HTTPS)

3. **Run Laravel commands**:

    ```bash
    docker compose exec app php artisan migrate
    docker compose exec app php artisan key:generate
    ```

4. **Xdebug Usage**:
    - Xdebug is automatically enabled in development
    - IDE should connect to `host.docker.internal:9003`
    - Coverage reports work as expected

### Production Environment

1. **Update Caddyfile for your domain**:

    ```bash
    # Edit docker/caddy/Caddyfile
    # Uncomment and modify the production configuration:
    your-domain.com {
        php_server {
            root /app/public
        }
        # ... rest of configuration
    }
    ```

2. **Build production image**:

    ```bash
    # Update docker-compose.yml to use Dockerfile instead of Dockerfile-dev
    docker compose build app
    ```

3. **Deploy**:
    - Caddy will automatically obtain SSL certificates from Let's Encrypt
    - No manual certificate configuration needed
    - HTTPS will be enforced automatically

## Configuration Files

### Caddyfile Structure

```caddyfile
# HTTP configuration for localhost development
localhost, 127.0.0.1, :80 {
    php_server {
        root /app/public
    }
    # Security headers, static assets, logging...
}

# Production HTTPS with automatic SSL (commented by default)
# your-domain.com {
#     php_server {
#         root /app/public
#     }
#     # Enhanced security, logging...
# }
```

### Docker Compose Integration

```yaml
app:
    environment:
        COMMAND: frankenphp run --config /etc/caddy/Caddyfile
    volumes:
        - ./docker/caddy/Caddyfile:/etc/caddy/Caddyfile
        - caddy_data:/data
        - caddy_config:/config
    ports:
        - '80:80'
        - '443:443'
        - '443:443/udp' # HTTP/3 support
```

## Performance Benefits

### FrankenPHP Advantages

- **Native PHP Integration**: No FastCGI overhead
- **Worker Mode**: Persistent application state
- **HTTP/2 & HTTP/3**: Modern protocol support
- **Memory Efficiency**: Lower memory footprint than traditional setups

### Caddy Advantages

- **Automatic HTTPS**: Zero-configuration SSL certificates
- **Modern Protocols**: HTTP/2, HTTP/3, gRPC support
- **Security**: Secure defaults and headers
- **Performance**: Efficient static file serving

## Troubleshooting

### Common Issues

1. **Certificate Warnings in Development**:
    - Expected behavior with self-signed certificates
    - Click "Advanced" → "Proceed to localhost" in browser
    - Or add exception in browser settings

2. **Port Conflicts**:

    ```bash
    # Stop conflicting services
    docker compose down --remove-orphans
    # Check port usage
    lsof -i :80 -i :443
    ```

3. **SSL Issues**:

    ```bash
    # Check Caddy logs
    docker compose logs app
    # Test certificate
    openssl s_client -connect localhost:443 -servername localhost
    ```

4. **Xdebug Connection Issues**:
    - Ensure IDE listens on `host.docker.internal:9003`
    - Check firewall settings
    - Verify `XDEBUG_MODE=debug,coverage` in environment

## Migration Checklist

- [x] Updated Dockerfiles to use FrankenPHP Alpine images
- [x] Created Caddy configuration with SSL support
- [x] Preserved Xdebug functionality for development
- [x] Maintained Docker Compose network structure
- [x] Added automatic HTTPS support
- [x] Tested HTTP/HTTPS functionality
- [x] Verified Laravel integration

## Next Steps

1. **Remove Legacy Files** (optional):

    ```bash
    rm -rf docker/nginx/
    ```

2. **Update CI/CD** (if applicable):
    - Update build scripts to use new Dockerfiles
    - Verify SSL certificate handling in production

3. **Domain Configuration** (for production):
    - Update Caddyfile with your actual domain
    - Configure DNS to point to your server
    - Caddy will automatically obtain SSL certificates

## Support

The migration is complete and all core functionality is working:

- ✅ HTTP/HTTPS access
- ✅ Laravel application running
- ✅ Xdebug support
- ✅ Automatic SSL certificates
- ✅ Process management
- ✅ Static asset optimization

Your application is now running on a modern, efficient FrankenPHP + Caddy stack
with automatic SSL support!
