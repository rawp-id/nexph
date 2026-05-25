# Arsitektur Stateful NexPH

NexPH stateful runtime adalah HTTP server, WebSocket server, dan background runtime berbasis PHP CLI. Ia tidak memakai Swoole, RoadRunner, ReactPHP, atau Amp. Semua mekanik utama dibuat di repo ini: event loop, coroutine, connection lifecycle, WebSocket lifecycle, queue, scheduler, memory monitor, GC trigger, observability, object pooling, dan backpressure.

Runtime ini sengaja tetap eksplisit. PHP bisa berjalan stateful, tetapi berbeda dari PHP-FPM: proses tidak mati setelah satu request. Karena itu server harus membersihkan koneksi, buffer, coroutine, timer, job object, dan cyclic reference sendiri supaya proses long-running tetap stabil.

## Gambaran Besar

```
Client
  |
  v
+---------------------------+
| serve.php                 |
| mode, workers, limits     |
+------------+--------------+
             |
             v
+---------------------------+      +--------------------------+
| core/Server               |      | core/Runtime             |
| EventLoop + HttpServer    |      | Fiber loop + workers     |
| Router + Middleware       |      | Queue + Scheduler        |
| Connection + WebSocket    |      | Channel + Timer          |
+------------+--------------+      +------------+-------------+
             |                                  |
             v                                  v
+---------------------------+      +--------------------------+
| HTTP/WS handlers          |      | Background jobs/tasks    |
| /api/*, /ws               |      | retry, cron, observers   |
+---------------------------+      +--------------------------+
```

Ada dua loop berbeda:

- `core/Server/EventLoop.php`: loop generator sederhana untuk HTTP server, socket readiness, timer, dan deferred callback.
- `core/Runtime/FiberEventLoop.php`: loop Fiber untuk queue, scheduler, timer runtime, channel, dan coroutine background.

Pemisahan ini menjaga HTTP path tetap ringan, sementara runtime background tetap bisa memakai Fiber API yang lebih nyaman.

## Startup Server

Server dimulai dari `serve.php`.

```bash
php serve.php --mode=http --workers=12 --max-connections=5000 --backlog=10000
```

Opsi penting:

| Opsi | Fungsi |
|------|--------|
| `--mode` | `http`, `ws`, `sse`, atau `all`; default `http` |
| `--host` | Bind address, default `0.0.0.0` |
| `--port` | TCP port, default `8080` HTTP, `8081` WS, `8082` SSE |
| `--workers` | Jumlah proses worker via `pcntl_fork()` |
| `--max-connections` | Batas active connection per worker |
| `--backlog` | Listen backlog yang dikirim ke socket context |
| `--max-requests` | Batas request per keep-alive connection; default auto `10000` |
| `--rate-limit` | `auto`, angka, atau `off`; mode WS default `off` |
| `--websocket` | `on` atau `off`; default `off` di mode HTTP dan `on` di mode WS/all |
| `--ws-path` | Path WebSocket, default `/ws` |
| `--websocket-timeout` | Idle timeout WebSocket dalam detik, default `300` |
| `--sse` | `on` atau `off`; default `off` di mode HTTP, `on` di mode SSE/all |
| `--sse-path` | Path Server-Sent Events, default `/events` |
| `--sse-heartbeat` | Interval heartbeat comment SSE dalam detik, default `15` |
| `--sse-timeout` | Idle timeout SSE dalam detik, default `300` |
| `--debug` | Log koneksi dan response, jangan dipakai untuk benchmark |

Worker model memakai multi-process. Jika `--workers=12`, proses parent melakukan fork sampai ada 12 worker yang sama-sama bind port dengan `so_reuseport`. Setiap worker punya event loop, connection table, counter, dan memory monitor sendiri.

Stats worker dipublish ke file stats bersama di `statsDir`. `/api/health` dan `/api/metrics` mengagregasi file stats semua worker yang masih segar, sehingga health endpoint tidak lagi misleading saat multi-worker.

Mode yang direkomendasikan:

```bash
# HTTP worker pool
php serve.php --mode=http --port=8080 --workers=12 --websocket=off

# WebSocket worker pool terpisah
php serve.php --mode=ws --port=8081 --workers=4 --ws-path=/ws

# SSE worker pool terpisah
php serve.php --mode=sse --port=8082 --workers=4 --sse-path=/events

# Mode gabungan jika ingin satu proses/port
php serve.php --mode=all --port=8080 --workers=12
```

SSE sengaja bisa dijalankan sebagai sub-runtime sendiri lewat `--mode=sse`. Mode ini tidak memuat route demo HTTP, static file, atau koneksi DB demo sehingga long-lived stream tidak ikut membebani pool REST/web. `--mode=all` tetap tersedia untuk development atau deployment kecil yang ingin HTTP, WS, dan SSE dalam satu port.

Pada multi-worker, publish SSE memakai file bus internal di `statsDir/sse-bus.log`. Artinya `POST /api/sse/publish` boleh masuk worker berbeda dari worker yang memegang koneksi `/events`; worker lain akan mempoll bus dan mengirim event ke client lokalnya. Field response `sent` adalah jumlah koneksi lokal pada worker penerima request, sedangkan `queued=true` berarti event sudah dipublish ke bus untuk worker lain. Channel ringan tersedia lewat `/events?channel=orders` dan publish JSON `{"channel":"orders","event":"tick","data":{...}}`.

## HTTP Server

Komponen utama di `core/Server/`:

| File | Fungsi |
|------|--------|
| `HttpServer.php` | Membuat socket server, accept batch koneksi, request lifecycle, cleanup, GC |
| `EventLoop.php` | `stream_select()` loop, reader/writer watcher, timer, deferred callback |
| `Connection.php` | State socket, read buffer, write buffer, keep-alive, request count |
| `HttpParser.php` | Parse request HTTP/1.0/1.1 dan build response |
| `ServerRequest.php` | Request/response object untuk handler |
| `WebSocket.php` | Handshake, frame encode/decode, opcode ping/pong/close |
| `ObjectPool.php` | Pool object reusable untuk hot path response |
| `Router.php` | Route matching, middleware chain, route params |
| `Coroutine.php` | Generator coroutine untuk handler server |
| `AsyncIO.php` | File/http helper dan DB helper berbasis generator, PDO pool, statement cache |
| `StaticFiles.php` | Static file handler |
| `Middleware/` | CORS, security headers, rate limit, request size/timeout |

### Accept Loop

Ketika socket server readable, server tidak accept satu koneksi saja. Ia batch accept sampai 128 koneksi per tick:

```php
private function acceptConnections(): void {
    for ($i = 0; $i < 128; $i++) {
        if (!$this->acceptConnection()) {
            break;
        }
    }
}
```

Ini penting untuk burst seperti 10.000 concurrent connections. Tanpa batch accept, backlog bisa penuh sementara event loop baru menerima satu koneksi per tick.

### Backpressure

Server tetap punya hard cap:

```php
if (count($this->connections) >= $this->maxConnections) {
    503 Server too busy
}
```

Ini bukan bug, tetapi backpressure. Tanpa batas, proses PHP bisa kehabisan file descriptor, memory, atau membuat event loop stall. Untuk kapasitas total, kalikan:

```
total_capacity ~= workers * max_connections
```

Contoh:

```
12 workers * 5000 max_connections = 60000 active connections teoritis
```

Batas OS tetap berlaku: `ulimit -n`, `net.core.somaxconn`, `net.ipv4.tcp_max_syn_backlog`, ephemeral port range, CPU, dan memory.

### Keep-Alive Pressure Control

Keep-alive diizinkan hanya saat koneksi belum terlalu banyak:

```php
$keepAlive = $request->wantsKeepAlive()
    && $conn->getRequestCount() < $this->maxRequestsPerConnection
    && count($this->connections) < $this->maxConnections * 0.8;
```

Saat active connection lewat 80% dari limit worker, server menutup response setelah request selesai. Ini mengorbankan reuse koneksi supaya slot cepat kembali tersedia untuk koneksi baru.

### Non-blocking Write

Response ditulis ke `Connection::write()`. Kalau `fwrite()` belum menulis semua data, sisanya masuk `writeBuffer`, lalu socket didaftarkan sebagai writer:

```php
if ($conn->hasWriteBuffer()) {
    $this->flushPending($conn, !$conn->isKeepAlive());
    return;
}
```

Dengan ini response besar tidak langsung memblokir event loop sampai seluruh buffer terkirim.

## WebSocket Server

WebSocket bisa jalan gabung dengan HTTP (`--mode=all`) atau lebih disarankan pisah worker dan port (`--mode=ws`). WebSocket adalah long-lived connection, jadi tuning-nya berbeda dari HTTP request pendek.

```bash
php serve.php --mode=ws --port=8081 --workers=4 --ws-path=/ws
```

Lifecycle:

```
HTTP GET /ws Upgrade
  |
  v
WebSocket::handshake()
  |
  v
Connection::markWebSocket()
  |
  v
frame read loop
  |
  v
ping/pong, text/binary, close frame
```

Fitur yang sudah ada:

- handshake HTTP 101 dengan `Connection: Upgrade`,
- frame parser dan encoder,
- text/binary frame,
- ping/pong,
- close frame agar client tidak melihat `1006 Abnormal Closure`,
- timeout khusus WebSocket,
- `onOpen`, `onMessage`, `onClose`,
- broadcast lokal worker,
- broadcast lintas worker lewat shared file bus,
- chunked broadcast per tick supaya 1000+ client tidak menahan event loop terlalu lama,
- read buffer dan frame size limit agar client lambat/besar tidak menahan memory,
- optional presence event (`join`/`leave`) yang bisa dimatikan untuk workload benchmark.

### Cross-worker Broadcast

Multi-worker memakai `so_reuseport`, sehingga client WebSocket bisa tersebar ke worker berbeda. Broadcast lokal saja tidak cukup. Karena itu server memakai file bus di `statsDir`:

```
worker A receives message
  |
  v
broadcast local clients
  |
  v
append event to websocket-bus.log
  |
  v
worker B/C/D poll every 50ms
  |
  v
deliver to local clients
```

`broadcastWebSocket()` menerima parameter internal `publish`. Event dari bus dipublish ulang dengan `publish=false` supaya tidak loop antar worker.

File bus cocok untuk runtime native tanpa dependency. Untuk production multi-host, bus ini bisa diganti Redis pub/sub, NATS, atau IPC dedicated.

Broadcast lokal tidak dikirim ke semua connection dalam satu tick. Runtime mengambil daftar target, lalu mengirim frame per batch melalui deferred callback. Ini menjaga tick tetap pendek saat ada fan-out besar, misalnya 1 sender mengirim ke 1000 WebSocket client.

## Request Flow

```
stream_select()
  |
  v
server socket readable
  |
  v
acceptConnections()
  |
  v
Connection registered as reader
  |
  v
Connection::read()
  |
  v
HttpParser::parseRequest()
  |
  v
ServerRequest + ServerResponse
  |
  v
middleware chain
  |
  v
Router::dispatch()
  |
  v
handler/generator coroutine
  |
  v
ServerResponse::build()
  |
  v
Connection::write() / flushPending()
  |
  v
keep-alive or closeConnection()
```

## GC Dan Memory Management

Bagian ini penting untuk PHP stateful. Di PHP-FPM, proses sering pendek atau di-recycle oleh process manager. Di server stateful, proses hidup lama, jadi cyclic reference dan buffer harus dibersihkan secara aktif.

### Connection Cleanup

`HttpServer` memasang timer cleanup setiap 1 detik:

```php
$this->loop->addTimer(1.0, function () {
    $this->cleanupConnections();
}, periodic: true);
```

`cleanupConnections()` menutup koneksi jika:

- socket tidak alive,
- koneksi idle lebih lama dari `keep_alive_timeout`,
- request terlalu besar,
- write gagal,
- server shutdown.

Saat koneksi ditutup:

```php
$this->loop->removeReader($socket);
$this->loop->removeWriter($socket);
$conn->close();
unset($this->connections[$id]);
```

`Connection::close()` juga mengosongkan buffer:

```php
$this->buffer = '';
$this->writeBuffer = '';
```

Ini menghindari retained memory dari request/response lama.

### Explicit GC Trigger

Setelah cleanup idle connection, server memanggil GC kalau banyak koneksi dibersihkan:

```php
if ($cleaned > 50) {
    gc_collect_cycles();
}
```

Ini membantu pada burst besar. Saat banyak `Connection`, closure reader/writer, request object, response object, dan coroutine selesai hampir bersamaan, PHP mungkin menyimpan cyclic reference sampai GC berikutnya. Explicit GC membuat memory lebih cepat kembali stabil.

GC tidak dipanggil setiap request karena mahal. Threshold `cleaned > 50` menjaga GC hanya berjalan setelah cleanup batch yang cukup besar.

### MemoryMonitor

`MemoryMonitor` mengambil sample setiap 10 detik dari HTTP server:

```php
$this->memoryMonitor->sample();
if ($this->memoryMonitor->detectLeak()) {
    $this->log("Warning: Memory leak detected - " . $this->memoryMonitor->getReport());
}
```

Cara kerjanya:

- menyimpan maksimal 100 sample,
- sample memakai head index supaya tidak `array_shift()` terus-menerus,
- leak check memakai window 20 sample terakhir,
- leak dicurigai jika growth > 1MB, growth rate > 10%, dan kenaikan cukup monotonic.

Output stats health:

```json
{
  "current": 10485760,
  "peak": 12582912,
  "samples": 100,
  "trend": "stable"
}
```

### Object Pooling

Hot path HTTP sekarang memakai `ObjectPool` untuk `ServerResponse`. Setelah response dibuild dan data diserahkan ke `Connection::write()`, object response di-reset lalu dikembalikan ke pool.

Tujuannya bukan menaikkan throughput secara dramatis, tetapi mengurangi allocation churn dan GC pressure pada long run.

Metric pool tersedia di `/api/metrics`:

```text
nexph_object_pool_idle{pool="response"}
nexph_object_pool_reused_total{pool="response"}
nexph_object_pool_created_total{pool="response"}
```

Pada long run 1000 VU selama 5 menit, response pool reuse mencapai jutaan reuse dan hampir semua request memakai object recycle.

Yang tidak dipool:

- `ServerRequest`, karena property-nya readonly dan request state lebih rawan bocor,
- `Connection`, karena lifecycle socket resource harus eksplisit,
- parsed header/query arrays, karena lebih aman tetap immutable per request.

## Observability

Endpoint runtime:

```bash
curl http://localhost:8080/api/health
curl http://localhost:8080/api/metrics
```

`/api/health` mengembalikan JSON agregat antar worker. `/api/metrics` memakai format Prometheus text.

Metrics utama:

| Metric | Fungsi |
|--------|--------|
| `nexph_http_requests_total` | Total request HTTP |
| `nexph_http_active_requests` | Request aktif |
| `nexph_http_responses_total{status}` | Response per status |
| `nexph_http_route_requests_total{method,path}` | Request per route ternormalisasi |
| `nexph_http_request_duration_ms_bucket` | Histogram latency HTTP internal |
| `nexph_runtime_loop_lag_ms` | EWMA event loop lag per tick |
| `nexph_runtime_loop_lag_max_ms` | Max event loop lag |
| `nexph_runtime_coroutines` | Coroutine handler aktif |
| `nexph_runtime_deferred_dropped_total` | Deferred callback yang ditolak karena queue penuh |
| `nexph_runtime_deferred_limit` | Batas deferred queue |
| `nexph_runtime_memory_pressure_events_total` | Perubahan state memory pressure |
| `nexph_runtime_memory_pressure_rejected_total` | Connection baru yang ditolak saat hard pressure |
| `nexph_runtime_memory_pressure_closed_total` | Connection idle yang ditutup saat hard pressure |
| `nexph_websockets_total` | Total upgrade WebSocket |
| `nexph_active_websockets` | WebSocket aktif |
| `nexph_websocket_bus_bytes` | Ukuran file bus WS |
| `nexph_websocket_broadcasts_total` | Jumlah panggilan broadcast WS |
| `nexph_websocket_broadcast_deliveries_total` | Total frame broadcast terkirim |
| `nexph_websocket_broadcast_batches_total` | Batch broadcast yang sudah diproses |
| `nexph_websocket_broadcast_pending_batches` | Batch broadcast yang masih antre |
| `nexph_websocket_read_limit_closes_total` | WS close karena read buffer melebihi limit |
| `nexph_websocket_frame_limit_closes_total` | WS close karena frame payload terlalu besar |
| `nexph_websocket_max_frame_bytes` | Batas payload frame WS |
| `nexph_websocket_max_read_buffer_bytes` | Batas read buffer WS |
| `nexph_sse_connections_total` | Total stream SSE yang diterima |
| `nexph_sse_active_connections` | Stream SSE aktif |
| `nexph_sse_events_sent_total` | Event SSE terkirim |
| `nexph_sse_heartbeats_total` | Heartbeat SSE terkirim |
| `nexph_sse_backpressure_closes_total` | SSE ditutup karena write buffer penuh |
| `nexph_sse_broadcasts_total` | Jumlah panggilan broadcast SSE |
| `nexph_sse_local_deliveries_total` | Delivery SSE lokal di worker |
| `nexph_sse_bus_published_total` | Event SSE yang masuk cross-worker bus |
| `nexph_sse_bus_deliveries_total` | Delivery SSE dari cross-worker bus |
| `nexph_sse_bus_bytes` | Ukuran file bus SSE |
| `nexph_object_pool_reused_total` | Reuse object pool |
| `nexph_object_pool_borrowed` | Object pool yang sedang dipakai |
| `nexph_object_pool_violations_total` | Double release, foreign release, contamination |
| `nexph_object_tracker_active` | Live object yang sedang dilacak runtime |
| `nexph_object_tracker_retained` | Object yang masih terikat context yang sudah close |
| `nexph_object_tracker_released_alive` | Object released yang masih hidup menurut `WeakMap` |
| `nexph_runtime_contexts_open` | Context request yang masih terbuka |
| `nexph_database_queries_total` | Total query DB helper |
| `nexph_database_query_avg_ms` | Rata-rata durasi query |

Loop lag dihitung sebagai delay per tick:

```text
lag = max(0, now - lastTick - interval)
lastTick = now
```

Ini menghindari drift akumulatif. Jika event loop benar-benar stuck, metric naik; jika hanya timer expected tertinggal, metric tidak salah membaca lag ratusan milidetik.

### Runtime Safety Rails

Runtime punya guardrail yang default-nya pasif dan murah di hot path:

- deferred queue limit mencegah callback numpuk tanpa batas,
- memory pressure mode dicek periodik, bukan tiap request,
- graceful drain menghentikan accept connection baru lalu menunggu connection aktif selesai,
- supervisor process restart worker yang crash dan bisa reload worker dengan `SIGUSR2`.

Signal lifecycle:

```text
SIGTERM/SIGINT -> graceful drain -> force stop after timeout
SIGUSR1        -> graceful drain worker
SIGUSR2        -> supervisor reload: spawn worker baru, drain worker lama
```

### Ownership dan Lifecycle

Runtime memakai ownership model eksplisit untuk object yang hidup lintas tick:

| Object | Owner | Lifecycle |
|--------|-------|-----------|
| `Connection` | `HttpServer::$connections` | dibuat saat accept, dihancurkan lewat `closeConnection()` |
| `ServerRequest` | request context | dibuat per request, tidak dipool, dilepas saat request selesai |
| `ServerResponse` | `ObjectPool response` | acquire per request, reset, release ke pool |
| WebSocket room membership | `HttpServer` | join saat open/message, leave saat close |

`ObjectPool` sekarang melacak state object:

- `borrowed`,
- `idle`,
- `dropped`,
- double release,
- foreign release,
- contamination sebelum reuse.

`ServerResponse` mengimplementasikan lifecycle contract:

- `Resettable::reset()`,
- `Cleanable::isClean()`.

Saat object response diambil dari pool, runtime memastikan object bersih. Jika object tidak bersih, counter contamination naik dan object di-reset sebelum dipakai ulang.

`ObjectTracker` memakai `WeakMap` untuk telemetry object tanpa menahan object agar tetap hidup. Tracker mencatat:

- live tracked object,
- retained object setelah context selesai,
- released object yang masih hidup,
- open/stale context,
- state per type.

### Queue dan Scheduler Cleanup

Queue worker juga melakukan cleanup:

- `Queue::stop()` menutup channel, await coroutine worker, kosongkan list worker, lalu `gc_collect_cycles()`.
- `Queue::processJob()` unset local reference `result`, `handlerInstance`, dan `handler` di `finally`.
- `FileDriver::pop()` menandai job sebagai `reserved` saat diambil supaya multi-worker tidak memproses file job yang sama.
- `ApcuDriver::pop()` menghapus queue entry yang corrupt/stale supaya APCu tidak menyimpan pointer job mati.
- `Schedule::executeTask()` menandai `isRunning` dan menolak overlap task yang sama. Task long-running tidak akan menumpuk coroutine baru setiap interval.

## Runtime Fiber

Komponen utama:

| File | Fungsi |
|------|--------|
| `Runtime.php` | Deteksi capability dan entrypoint spawn/sleep/yield/run |
| `FiberEventLoop.php` | Ready queue, sleeping fibers, timers |
| `FiberCoroutine.php` | Wrapper Fiber, return value, suspend reason |
| `Channel.php` | Buffered/unbuffered channel untuk komunikasi coroutine |
| `Timer.php` | API timer one-shot/repeating |

### Capability Detection

Runtime aktif jika PHP berjalan di CLI dan `Fiber` tersedia:

```php
Runtime::available() === fibers && cli
```

Jika tidak available, runtime fallback ke mode sinkron. Ini membuat fitur bisa tetap jalan di shared hosting/FPM, walau tanpa concurrency Fiber.

### Suspend Reason

Fiber memakai tiga reason:

```php
SUSPEND_YIELD
SUSPEND_SLEEP
SUSPEND_CHANNEL
```

Event loop hanya langsung menjadwalkan ulang coroutine yang melakukan yield sukarela:

```php
if (!$coroutine->isFinished()
    && $coroutine->lastSuspend() === FiberCoroutine::SUSPEND_YIELD) {
    $this->ready[] = $coroutine;
}
```

Fiber yang sleep menunggu wake time. Fiber yang menunggu channel hanya lanjut saat channel mengirim data atau ditutup. Ini mencegah busy loop dan mencegah coroutine sleep/channel langsung hidup lagi sebelum waktunya.

### Timer Idle Sleep

Jika tidak ada ready coroutine, loop menghitung wake time berikutnya dari sleeping fiber dan timer:

```php
$nextWake = $this->nextWakeTime();
usleep(min($sleepTime, 10ms));
```

Ini mencegah timer-only runtime memakan CPU saat idle.

### Channel

`Channel` mendukung:

- unbuffered channel (`capacity = 0`),
- buffered channel (`capacity > 0`),
- blocking send/receive via Fiber suspend,
- close yang membangunkan sender/receiver,
- queue head index untuk menghindari `array_shift()` di hot path.

Jika fiber dibangunkan dari channel lalu melakukan `Runtime::yield()`, channel akan membungkus ulang fiber menjadi `FiberCoroutine` dan memasukkannya kembali ke scheduler. Ini mencegah fiber "hilang" dari loop.

## Async Database Helper

`AsyncDatabase` berada di `core/Server/AsyncIO.php`. Namanya async dari sisi handler generator, tetapi driver PDO tetap blocking. Optimasi yang dilakukan adalah membuat blocking window sekecil mungkin dan mengurangi overhead per request.

Fitur:

- pool PDO per worker,
- persistent PDO optional,
- prepared statement cache per koneksi,
- SQLite WAL,
- SQLite `busy_timeout`,
- SQLite `synchronous=NORMAL`,
- SQLite `foreign_keys=ON`,
- stats query dan error,
- `SELECT`, `PRAGMA`, `WITH`, dan `EXPLAIN` otomatis dianggap return rows.

Konfigurasi dapat diberikan lewat config DB:

```php
'db' => [
    'driver' => 'sqlite',
    'database' => __DIR__ . '/../storage/database.sqlite',
    'pool_size' => 4,
    'statement_cache_size' => 128,
    'busy_timeout_ms' => 5000,
]
```

Untuk SQLite, pool membantu reuse PDO dan statement, tetapi write concurrency SQLite tetap dibatasi locking database. Untuk workload production yang banyak write, gunakan MySQL/Postgres atau pindahkan query berat ke queue/worker khusus.

## Queue

Queue berada di `core/Runtime/Queue/`.

| File | Fungsi |
|------|--------|
| `Queue.php` | Facade worker, work loop, retry, metrics |
| `QueueFactory.php` | Membuat driver queue |
| `QueueManager.php` | Mengatur banyak queue |
| `QueueMetrics.php` | Counter dan duration metrics |
| `QueueObserver.php` | Periodic reporting |
| `Job.php` | Struktur data job |
| `Drivers/MemoryDriver.php` | Queue in-memory |
| `Drivers/FileDriver.php` | Queue berbasis file |
| `Drivers/DatabaseDriver.php` | Queue berbasis PDO |
| `Drivers/RedisDriver.php` | Queue berbasis Redis sorted set |
| `Drivers/ApcuDriver.php` | Queue berbasis APCu |

### Async Worker Mode

Jika runtime Fiber tersedia:

1. `Queue::work()` memanggil `workAsync()`.
2. Dibuat `Channel(100)` sebagai buffer job.
3. Worker coroutine menjalankan `workerLoop()`.
4. Fetcher coroutine menjalankan `fetchLoop()`.
5. Metrics coroutine berjalan jika `metrics_interval > 0`.
6. `Runtime::run()` dijalankan jika loop belum berjalan.

Fetcher mengambil job dari driver dan mengirim ke channel. Worker menerima job dari channel dan memanggil `processJob()`.

### Sync Worker Mode

Jika runtime tidak tersedia, queue memakai loop blocking:

```php
while ($this->running) {
    if (!$this->workOnce(1)) {
        usleep($pollInterval);
    }
}
```

`workOnce()` juga dipakai `QueueManager` agar mode sync benar-benar memproses satu job per queue secara round-robin.

### Retry dan Dead Letter

Jika handler gagal:

- `attempts` bertambah,
- jika attempts habis, job masuk dead letter,
- jika masih bisa retry, `available_at` digeser dengan exponential backoff:

```php
$delay = retry_delay * pow(2, attempts - 1)
```

## Scheduler

Scheduler berada di `core/Runtime/Scheduler/`.

| File | Fungsi |
|------|--------|
| `Schedule.php` | Registry task, run loop async/sync, execute task |
| `ScheduledTask.php` | Definisi task, cron matcher, metadata |

Scheduler mendukung:

- `everyMinute()`
- `everyFiveMinutes()`
- `everyFifteenMinutes()`
- `everyThirtyMinutes()`
- `hourly()`
- `daily()`
- `weekly()`
- `cron()`
- `every($seconds)`

Task interval di async mode memakai timer repeating. Task cron di async mode memakai coroutine loop yang cek setiap 1 detik. Di sync mode semua task dicek per detik.

### Anti-overlap

`ScheduledTask` punya flag:

```php
public bool $isRunning = false;
public int $skippedCount = 0;
```

Jika task masih berjalan saat interval berikutnya tiba, run baru dilewati dan `skippedCount` naik. Ini mencegah task lambat menumpuk coroutine dan menyebabkan memory naik.

## Tuning

### HTTP Server

```php
$server = new HttpServer([
    'host' => '0.0.0.0',
    'port' => 8080,
    'max_connections' => 5000,
    'backlog' => 10000,
    'keep_alive_timeout' => 30,
    'max_requests' => 10000,
    'max_request_size' => 10 * 1024 * 1024,
    'max_deferred' => 100000,
    'memory_pressure_threshold' => 0.85,
    'memory_hard_pressure_threshold' => 0.95,
    'graceful_shutdown_timeout' => 30,
    'websocket_timeout' => 300,
    'websocket_broadcast_batch_size' => 250,
    'websocket_max_frame_size' => 1024 * 1024,
    'websocket_max_read_buffer_size' => 2 * 1024 * 1024,
    'sse_heartbeat_interval' => 15,
    'sse_timeout' => 300,
    'response_pool_size' => 2048,
    'debug' => false,
]);
```

Rekomendasi awal:

| Target | workers | max_connections | backlog | Catatan |
|--------|---------|-----------------|---------|---------|
| Dev | 1 | 1000 | 4096 | Mudah debug |
| Local benchmark | CPU core count | 3000-5000 | 10000 | Naikkan OS limits jika perlu |
| Production awal | CPU core count | sesuai memory budget | sesuai `somaxconn` | ukur memory per connection |

Rule of thumb:

```
total active connection cap = workers * max_connections
```

`backlog` tidak bisa melebihi limit efektif OS. Cek:

```bash
ulimit -n
sysctl net.core.somaxconn
sysctl net.ipv4.tcp_max_syn_backlog
```

### Queue

| Param | Fungsi |
|-------|--------|
| `workers` | Jumlah worker coroutine |
| `poll_interval` | Delay saat queue kosong |
| `retry_delay` | Delay awal retry |
| `max_attempts` | Batas retry job |
| `metrics_interval` | Interval print metrics |
| `quiet` | Matikan log worker |
| `verbose` | Log metrics lebih sering |

## Benchmark Terkini

Contoh hasil lokal setelah tuning:

```bash
php serve.php --mode=http --workers=12 --max-connections=5000 --backlog=10000
k6 run --summary-trend-stats "min,avg,med,p(90),p(95),p(99),p(99.9),max" scripts/k6-users.js
```

Hasil `/api/users` dengan 1000 VUs selama 5 menit:

| Metric | Nilai |
|--------|-------|
| Throughput | ~24.6K req/s |
| Total requests | ~7.39M |
| Avg | ~36.7ms |
| P95 | ~61ms |
| P99 | ~89ms |
| P99.9 | ~135ms |
| Max | ~258ms |
| Error rate | 0.00% |
| Loop lag EWMA | ~0.04ms |
| Response pool reuse | ~7.39M |

Hasil terbaik 30 detik setelah DB/pool tuning:

| Metric | Nilai |
|--------|-------|
| Throughput | ~23.5K req/s |
| Avg | ~37ms |
| P95 | ~72ms |
| P99 | ~104ms |
| P99.9 | ~151ms |
| Error rate | 0.00% |

Burst test:

```bash
php scripts/server-test.php --requests=1000000 --concurrency=10000
```

Dengan 12 worker dan connection cap cukup besar, 10.000 concurrent requests berhasil. Latency burst bisa tinggi karena test membuka 10.000 koneksi sekaligus dari satu client; itu lebih menguji accept backlog, kernel scheduling, curl_multi, dan FD pressure daripada latency steady-state.

## Struktur File Aktual

```
core/
├── Server/
│   ├── AsyncIO.php
│   ├── Connection.php
│   ├── Coroutine.php
│   ├── EventLoop.php
│   ├── HttpParser.php
│   ├── HttpServer.php
│   ├── ObjectPool.php
│   ├── Router.php
│   ├── ServerRequest.php
│   ├── StaticFiles.php
│   ├── WebSocket.php
│   └── Middleware/
│
└── Runtime/
    ├── Channel.php
    ├── ConnectionPool.php
    ├── Daemon.php
    ├── FiberCoroutine.php
    ├── FiberEventLoop.php
    ├── MemoryLeakDetector.php
    ├── MemoryMonitor.php
    ├── ProcessManager.php
    ├── Runtime.php
    ├── Socket.php
    ├── Timer.php
    ├── Worker.php
    ├── CLI/
    ├── Observability/
    ├── Queue/
    ├── Scheduler/
    └── Supervisor/
```

## Operasional

### Start

```bash
php serve.php --mode=http --workers=12 --max-connections=5000 --backlog=10000
php serve.php --mode=ws --port=8081 --workers=4 --ws-path=/ws
```

### Test

```bash
k6 run scripts/k6-users.js
php scripts/server-test.php --requests=1000000 --concurrency=10000
```

### Health

```bash
curl http://localhost:8080/api/health
curl http://localhost:8080/api/metrics
```

Health dan metrics sudah agregat antar worker melalui file stats di `statsDir`. Untuk multi-host, ganti collector file dengan Redis, shared storage, atau parent-process collector.
