# Runtime Deployment Guide

Complete guide for deploying Nexph with runtime features.

## Deployment Modes

### Mode 1: Shared Hosting (Stateless Only)

**Environment:**
- Apache/Nginx + PHP-FPM
- No CLI access
- Any PHP version

**Setup:**

1. Upload files via FTP/SFTP
2. Configure `.htaccess` or nginx config
3. Set environment variables in `.env`

**No runtime configuration needed** — Everything works stateless.

**Example `.htaccess`:**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

**Performance:**
- Request latency: 10-20ms
- Concurrency: 20-50 (process pool)
- Memory: 5-10MB per request

---

### Mode 2: VPS with Workers (Hybrid)

**Environment:**
- Nginx + PHP-FPM (web traffic)
- CLI access (background workers)
- PHP 8.1+

**Setup:**

#### 1. Web Server (Nginx + FPM)

**nginx.conf:**
```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/nexph/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

**php-fpm pool:**
```ini
[nexph]
user = www-data
group = www-data
listen = /var/run/php/php8.1-fpm.sock
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 10
```

#### 2. Background Worker (CLI)

**Create worker script:**
```php
<?php
// worker.php
require_once __DIR__ . '/runtime/autoload.php';
require_once __DIR__ . '/autoload.php';

use Runtime\Worker;
use Core\Queue\Queue;
use Core\Database\DB;
use Core\Support\Config;

Config::loadEnv(__DIR__ . '/.env');
DB::connect(Config::get('db'));

Worker::start(function() {
    $job = Queue::pop();
    if ($job) {
        echo "[Worker] Processing job: {$job['id']}\n";
        // Process job
        Queue::complete($job['id']);
    }
}, [
    'sleep' => 1.0,
]);
```

#### 3. Systemd Service

**Create `/etc/systemd/system/nexph-worker.service`:**
```ini
[Unit]
Description=Nexph Background Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/nexph
ExecStart=/usr/bin/php8.1 /var/www/nexph/worker.php
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

**Enable and start:**
```bash
sudo systemctl daemon-reload
sudo systemctl enable nexph-worker
sudo systemctl start nexph-worker
sudo systemctl status nexph-worker
```

**View logs:**
```bash
sudo journalctl -u nexph-worker -f
```

#### 4. Multiple Workers

**Create worker pool:**
```bash
# Create 4 worker instances
for i in {1..4}; do
    sudo cp /etc/systemd/system/nexph-worker.service \
           /etc/systemd/system/nexph-worker@$i.service
done

sudo systemctl daemon-reload
sudo systemctl enable nexph-worker@{1..4}
sudo systemctl start nexph-worker@{1..4}
```

**Performance:**
- Web: 10-20ms latency
- Workers: 1000+ jobs/minute
- Concurrency: Thousands (async)

---

### Mode 3: Dedicated Server (Full Runtime)

**Environment:**
- Full control
- Multiple workers
- Socket server
- PHP 8.1+ with all extensions

**Setup:**

#### 1. Install Extensions

```bash
# Ubuntu/Debian
sudo apt-get install php8.1-cli php8.1-fpm php8.1-pcntl \
                     php8.1-sockets php8.1-posix php8.1-redis

# Verify
php -m | grep -E "(pcntl|sockets|posix|redis)"
```

#### 2. Web Server (Same as Mode 2)

Use Nginx + PHP-FPM for HTTP traffic.

#### 3. Worker Pool

**Create `/etc/systemd/system/nexph-worker@.service`:**
```ini
[Unit]
Description=Nexph Worker %i
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/nexph
ExecStart=/usr/bin/php8.1 /var/www/nexph/worker.php --id=%i
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

**Start pool:**
```bash
sudo systemctl enable nexph-worker@{1..8}
sudo systemctl start nexph-worker@{1..8}
```

#### 4. Socket Server (Optional)

**Create socket server:**
```php
<?php
// socket_server.php
require_once __DIR__ . '/runtime/autoload.php';

use Runtime\Runtime;
use Runtime\Socket;

$server = Socket::tcp();
$server->bind('0.0.0.0', 9000);
$server->listen();
$server->setNonBlocking(true);

echo "Socket server listening on :9000\n";

Runtime::spawn(function() use ($server) {
    while (true) {
        $client = $server->accept();
        if ($client) {
            Runtime::spawn(function() use ($client) {
                $data = $client->read(8192);
                // Handle request
                $client->write("Response\n");
                $client->close();
            });
        }
        Runtime::yield();
    }
});

Runtime::run();
```

**Create systemd service:**
```ini
[Unit]
Description=Nexph Socket Server
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/nexph
ExecStart=/usr/bin/php8.1 /var/www/nexph/socket_server.php
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

**Performance:**
- Web: 5-10ms latency
- Workers: 5000+ jobs/minute
- Sockets: 10,000+ connections
- Concurrency: Tens of thousands

---

## Docker Deployment

### Dockerfile

```dockerfile
FROM php:8.1-cli

# Install extensions
RUN docker-php-ext-install pcntl sockets posix pdo pdo_mysql

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Copy application
COPY . /app
WORKDIR /app

# Run worker
CMD ["php", "worker.php"]
```

### docker-compose.yml

```yaml
version: '3.8'

services:
  web:
    image: php:8.1-fpm
    volumes:
      - ./:/var/www/nexph
    networks:
      - nexph

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - ./:/var/www/nexph
      - ./nginx.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - web
    networks:
      - nexph

  worker:
    build: .
    command: php worker.php
    volumes:
      - ./:/app
    depends_on:
      - web
    deploy:
      replicas: 4
    networks:
      - nexph

  redis:
    image: redis:alpine
    networks:
      - nexph

networks:
  nexph:
```

**Start:**
```bash
docker-compose up -d
docker-compose ps
docker-compose logs -f worker
```

---

## Kubernetes Deployment

### Deployment YAML

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: nexph-web
spec:
  replicas: 3
  selector:
    matchLabels:
      app: nexph-web
  template:
    metadata:
      labels:
        app: nexph-web
    spec:
      containers:
      - name: php-fpm
        image: nexph:latest
        ports:
        - containerPort: 9000
        env:
        - name: APP_ENV
          value: production

---
apiVersion: apps/v1
kind: Deployment
metadata:
  name: nexph-worker
spec:
  replicas: 8
  selector:
    matchLabels:
      app: nexph-worker
  template:
    metadata:
      labels:
        app: nexph-worker
    spec:
      containers:
      - name: worker
        image: nexph:latest
        command: ["php", "worker.php"]
        env:
        - name: APP_ENV
          value: production

---
apiVersion: v1
kind: Service
metadata:
  name: nexph-web
spec:
  selector:
    app: nexph-web
  ports:
  - port: 80
    targetPort: 9000
  type: LoadBalancer
```

**Deploy:**
```bash
kubectl apply -f deployment.yaml
kubectl get pods
kubectl logs -f deployment/nexph-worker
```

---

## Monitoring

### Health Checks

**Web health check:**
```php
$router->add('GET', '/health', function($req, $res) {
    $res->json([
        'status' => 'ok',
        'timestamp' => time(),
    ]);
});
```

**Worker health check:**
```php
// In worker.php
file_put_contents('/tmp/nexph-worker-heartbeat', time());
```

**Monitor script:**
```bash
#!/bin/bash
# check_worker.sh

HEARTBEAT_FILE="/tmp/nexph-worker-heartbeat"
MAX_AGE=60

if [ ! -f "$HEARTBEAT_FILE" ]; then
    echo "Worker not running"
    exit 1
fi

AGE=$(($(date +%s) - $(cat $HEARTBEAT_FILE)))

if [ $AGE -gt $MAX_AGE ]; then
    echo "Worker stale (${AGE}s old)"
    exit 1
fi

echo "Worker healthy"
exit 0
```

### Metrics Collection

**Prometheus exporter:**
```php
<?php
// metrics.php
require_once __DIR__ . '/runtime/autoload.php';

use Runtime\Runtime;

$router->add('GET', '/metrics', function($req, $res) {
    $metrics = [
        'nexph_runtime_available' => Runtime::available() ? 1 : 0,
        'nexph_worker_jobs_processed' => getJobCount(),
        'nexph_worker_uptime' => getUptime(),
    ];
    
    $output = '';
    foreach ($metrics as $name => $value) {
        $output .= "{$name} {$value}\n";
    }
    
    $res->header('Content-Type', 'text/plain');
    $res->send($output);
});
```

### Logging

**Structured logging:**
```php
<?php
// In worker.php

function logWorker(string $level, string $message, array $context = []): void {
    $log = [
        'timestamp' => date('c'),
        'level' => $level,
        'message' => $message,
        'context' => $context,
        'worker_id' => getmypid(),
    ];
    
    error_log(json_encode($log));
}

Worker::start(function() {
    logWorker('info', 'Processing job', ['job_id' => 123]);
});
```

**Log aggregation (rsyslog):**
```conf
# /etc/rsyslog.d/nexph.conf
:programname, isequal, "nexph-worker" /var/log/nexph/worker.log
& stop
```

---

## Performance Tuning

### PHP Configuration

**php.ini:**
```ini
; Memory
memory_limit = 256M

; OPcache
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 0

; Realpath cache
realpath_cache_size = 4096K
realpath_cache_ttl = 600

; Disable unused extensions
disable_functions = exec,passthru,shell_exec,system
```

### Worker Tuning

**Optimize worker sleep:**
```php
Worker::start(function() {
    $job = Queue::pop();
    if ($job) {
        processJob($job);
    }
}, [
    'sleep' => $job ? 0.1 : 1.0, // Fast when busy, slow when idle
]);
```

**Batch processing:**
```php
Worker::start(function() {
    $jobs = Queue::popBatch(10); // Process 10 at once
    foreach ($jobs as $job) {
        processJob($job);
    }
}, [
    'sleep' => 0.5,
]);
```

### Database Connection Pooling

**Persistent connections:**
```php
DB::connect([
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'nexph',
    'username' => 'user',
    'password' => 'pass',
    'options' => [
        PDO::ATTR_PERSISTENT => true,
    ],
]);
```

---

## Troubleshooting

### Worker Not Starting

**Check PHP version:**
```bash
php -v
# Must be 8.1+
```

**Check extensions:**
```bash
php -m | grep -E "(pcntl|sockets)"
```

**Check permissions:**
```bash
sudo -u www-data php worker.php
```

**Check logs:**
```bash
sudo journalctl -u nexph-worker -n 50
```

### High Memory Usage

**Profile worker:**
```php
Worker::start(function() {
    $before = memory_get_usage();
    
    // Work
    
    $after = memory_get_usage();
    $diff = $after - $before;
    
    if ($diff > 1024 * 1024) { // 1MB
        error_log("High memory usage: " . ($diff / 1024 / 1024) . "MB");
    }
});
```

**Add memory limit:**
```php
Worker::start(function() {
    if (memory_get_usage() > 100 * 1024 * 1024) { // 100MB
        Worker::stop();
        exit(0); // Systemd will restart
    }
});
```

### Slow Performance

**Enable query logging:**
```php
use Core\Database\QueryLogger;

QueryLogger::enable();

// After requests
$queries = QueryLogger::get();
foreach ($queries as $q) {
    if ($q['time'] > 0.1) { // Slow query
        error_log("Slow query: {$q['sql']} ({$q['time']}s)");
    }
}
```

**Profile event loop:**
```php
$start = microtime(true);
Runtime::run();
$elapsed = microtime(true) - $start;

if ($elapsed > 1.0) {
    error_log("Slow event loop: {$elapsed}s");
}
```

---

## Security

### Process Isolation

**Run as dedicated user:**
```bash
sudo useradd -r -s /bin/false nexph-worker
sudo chown -R nexph-worker:nexph-worker /var/www/nexph
```

**Update systemd service:**
```ini
[Service]
User=nexph-worker
Group=nexph-worker
```

### Resource Limits

**Systemd limits:**
```ini
[Service]
LimitNOFILE=10000
LimitNPROC=100
MemoryLimit=512M
CPUQuota=50%
```

### Network Security

**Firewall rules:**
```bash
# Allow HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Block socket server from external
sudo ufw deny 9000/tcp
```

**Bind to localhost:**
```php
$server->bind('127.0.0.1', 9000); // Not 0.0.0.0
```

---

## Backup & Recovery

### Database Backup

**Automated backup:**
```bash
#!/bin/bash
# backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/nexph"

mkdir -p $BACKUP_DIR

# SQLite
cp /var/www/nexph/database.sqlite $BACKUP_DIR/database_$DATE.sqlite

# MySQL
mysqldump -u user -p nexph > $BACKUP_DIR/nexph_$DATE.sql

# Compress
gzip $BACKUP_DIR/*_$DATE.*

# Keep last 7 days
find $BACKUP_DIR -mtime +7 -delete
```

**Cron job:**
```cron
0 2 * * * /usr/local/bin/backup.sh
```

### Worker State Recovery

**Graceful restart:**
```bash
# Stop workers gracefully
sudo systemctl stop nexph-worker@*

# Wait for jobs to complete
sleep 10

# Start workers
sudo systemctl start nexph-worker@*
```

**Zero-downtime deployment:**
```bash
# Deploy new code
git pull

# Reload workers one by one
for i in {1..8}; do
    sudo systemctl restart nexph-worker@$i
    sleep 5
done
```

---

## Scaling

### Horizontal Scaling

**Add more workers:**
```bash
# Scale to 16 workers
sudo systemctl enable nexph-worker@{9..16}
sudo systemctl start nexph-worker@{9..16}
```

**Load balancer:**
```nginx
upstream nexph_backend {
    server 10.0.1.10:80;
    server 10.0.1.11:80;
    server 10.0.1.12:80;
}

server {
    listen 80;
    location / {
        proxy_pass http://nexph_backend;
    }
}
```

### Vertical Scaling

**Increase worker count per server:**
```bash
# Check CPU cores
nproc

# Scale workers to 2x cores
WORKERS=$(($(nproc) * 2))
sudo systemctl enable nexph-worker@{1..$WORKERS}
```

**Increase PHP-FPM pool:**
```ini
pm.max_children = 100
pm.start_servers = 20
```

---

## Best Practices

1. **Separate web and workers** — Different resource profiles
2. **Monitor everything** — Health checks, metrics, logs
3. **Graceful shutdown** — Always handle SIGTERM
4. **Resource limits** — Prevent runaway processes
5. **Automated recovery** — Systemd restart on failure
6. **Regular backups** — Database and configuration
7. **Security hardening** — Dedicated users, firewall rules
8. **Performance testing** — Load test before production
9. **Gradual rollout** — Deploy to subset first
10. **Documentation** — Keep runbooks updated

---

## Quick Reference

### Common Commands

```bash
# Start worker
sudo systemctl start nexph-worker

# Stop worker
sudo systemctl stop nexph-worker

# Restart worker
sudo systemctl restart nexph-worker

# View logs
sudo journalctl -u nexph-worker -f

# Check status
sudo systemctl status nexph-worker

# Test runtime
php -r "require 'runtime/autoload.php'; var_dump(Runtime\Runtime::available());"
```

### Configuration Files

```
/etc/systemd/system/nexph-worker.service    — Worker service
/etc/nginx/sites-available/nexph            — Nginx config
/etc/php/8.1/fpm/pool.d/nexph.conf         — PHP-FPM pool
/var/www/nexph/.env                         — Environment config
/var/www/nexph/worker.php                   — Worker script
```

### Log Files

```
/var/log/nginx/access.log                   — Web access
/var/log/nginx/error.log                    — Web errors
/var/log/php8.1-fpm.log                     — PHP-FPM logs
journalctl -u nexph-worker                  — Worker logs
```
