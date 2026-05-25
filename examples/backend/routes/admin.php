<?php

use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Middleware;
use Core\Database\DB;
use Core\Database\Migration;
use Core\Database\Metadata;
use Core\UI\View;
use Core\Http\ApiPolicy;

$router->add('GET', '/admin', function (Request $req, Response $res) use ($allMeta) {
    $content = "<div class='mb-8'>
        <h3 class='text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight'>Infrastructure</h3>
        <p class='text-slate-500 mt-1'>Manage your database schemas and real-time data</p>
    </div>";
    $content .= "<div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6'>";
    foreach ($allMeta as $m) {
        if ($m['table'] === 'job_workers') continue;
        $fieldsCount = count($m['fields']);
        $isSynced = Migration::isSynced($m['table'], $m);
        $statusBadge = $isSynced
            ? "<span class='flex items-center gap-1 text-[10px] font-bold bg-emerald-500/10 text-emerald-500 px-2 py-0.5 rounded-full border border-emerald-500/20'><i data-lucide='check-circle' class='w-2.5 h-2.5'></i> Synced</span>"
            : "<span class='flex items-center gap-1 text-[10px] font-bold bg-amber-500/10 text-amber-500 px-2 py-0.5 rounded-full border border-amber-500/20 animate-pulse'><i data-lucide='alert-circle' class='w-2.5 h-2.5'></i> Out of Sync</span>";
        $recordCount = 0;
        $lastUpdate = 'Never';
        try {
            $countResult = DB::query("SELECT COUNT(*) as cnt FROM `{$m['table']}`");
            $recordCount = $countResult[0]['cnt'] ?? 0;
            $hasCreatedAt = false;
            foreach ($m['fields'] as $f) {
                if ($f['name'] === 'created_at' || $f['name'] === 'updated_at') {
                    $hasCreatedAt = true;
                    break;
                }
            }
            if ($hasCreatedAt) {
                $timeResult = DB::query("SELECT MAX(COALESCE(updated_at, created_at)) as last_time FROM `{$m['table']}`");
                if (!empty($timeResult[0]['last_time'])) {
                    $timestamp = strtotime($timeResult[0]['last_time']);
                    $diff = time() - $timestamp;
                    if ($diff < 60) $lastUpdate = 'Just now';
                    elseif ($diff < 3600) $lastUpdate = floor($diff / 60) . 'm ago';
                    elseif ($diff < 86400) $lastUpdate = floor($diff / 3600) . 'h ago';
                    else $lastUpdate = floor($diff / 86400) . 'd ago';
                }
            }
        } catch (\Exception $e) {
            $recordCount = '?';
            $lastUpdate = 'N/A';
        }
        $content .= "
        <div class='group relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl hover:border-indigo-500/50 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-all duration-300 shadow-xl overflow-hidden'>
            <div class='absolute top-0 right-0 p-4 opacity-5 dark:opacity-10 group-hover:opacity-20 transition-opacity'>
                <i data-lucide='database' class='w-16 h-16 text-indigo-500'></i>
            </div>
            <div class='flex items-start justify-between mb-4'>
                <div class='flex items-center gap-4'>
                    <div class='p-3 bg-indigo-600/10 rounded-xl text-indigo-600 dark:text-indigo-400 group-hover:bg-indigo-600 group-hover:text-white transition-all'>
                        <i data-lucide='table-2' class='w-6 h-6'></i>
                    </div>
                    <div>
                        <div class='flex items-center gap-2 mb-1'>
                            <h4 class='text-lg font-bold text-slate-900 dark:text-white'>{$m['table']}</h4>
                            {$statusBadge}
                        </div>
                        <p class='text-xs text-slate-500 font-medium'>{$recordCount} records</p>
                    </div>
                </div>
            </div>
            <div class='space-y-2 mb-4'>
                <div class='flex items-center justify-between text-xs text-slate-500 dark:text-slate-400'>
                    <span class='flex items-center gap-1.5'><i data-lucide='columns' class='w-3 h-3'></i> {$fieldsCount} Fields</span>
                    <span class='flex items-center gap-1.5'><i data-lucide='clock' class='w-3 h-3'></i> {$lastUpdate}</span>
                </div>
            </div>
            <div class='flex gap-2 pt-3 border-t border-slate-100 dark:border-slate-800/50'>
                <button hx-get='/admin/{$m['table']}' hx-target='#content' hx-push-url='true' class='flex-1 px-3 py-2 bg-indigo-600 text-white text-xs font-bold rounded-lg hover:bg-indigo-500 transition-all shadow-sm'>View Data</button>
                <button hx-get='/admin/{$m['table']}/create' hx-target='#content' hx-push-url='true' class='px-3 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-all'><i data-lucide='plus' class='w-3 h-3'></i></button>
            </div>
        </div>";
    }
    $content .= "
    <a hx-get='/admin/schema/new' hx-target='#content' hx-push-url='true' class='group bg-slate-100 dark:bg-slate-950 border-2 border-dashed border-slate-200 dark:border-slate-800 p-6 rounded-2xl flex flex-col items-center justify-center gap-3 hover:border-indigo-500/50 hover:bg-indigo-50 dark:hover:bg-indigo-500/5 transition-all text-slate-400 dark:text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer'>
        <i data-lucide='plus' class='w-8 h-8'></i>
        <span class='text-sm font-bold tracking-wide'>Create New Resource</span>
    </a>";
    $content .= "</div><script>lucide.createIcons();</script>";
    if ($req->header('HX-Request')) {
        echo $content;
        exit;
    }
    echo View::layout('Dashboard', $content, $allMeta);
    exit;
}, [Middleware::class . '::session']);
$router->add('GET', '/admin/jobs', function (Request $req, Response $res) use ($allMeta) {
    $jobs = DB::query("SELECT * FROM job_workers ORDER BY created_at DESC LIMIT 100");
    $content = "<div class='max-w-6xl mx-auto space-y-6'><div class='mb-8'><h3 class='text-2xl font-bold text-slate-900 dark:text-white'>Job Queue</h3><p class='text-slate-500 text-sm mt-1'>Monitor and manage background jobs</p></div>";
    if (empty($jobs)) {
        $content .= "<div class='bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-xl p-12 text-center'><i data-lucide='inbox' class='w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4'></i><p class='text-slate-500 font-medium'>No jobs in queue</p></div>";
    } else {
        $content .= "<div class='bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden'><table class='w-full'><thead class='bg-slate-50 dark:bg-slate-800/30 border-b border-slate-200 dark:border-slate-800'><tr><th class='px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase'>Job</th><th class='px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase'>Status</th><th class='px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase'>Progress</th><th class='px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase'>Attempts</th><th class='px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase'>Created</th></tr></thead><tbody class='divide-y divide-slate-200 dark:divide-slate-800'>";
        foreach ($jobs as $job) {
            $statusColor = match ($job['status']) {
                'completed' => 'emerald',
                'failed' => 'red',
                'running' => 'blue',
                default => 'amber'
            };
            $content .= "<tr class='hover:bg-slate-50 dark:hover:bg-slate-800/20'><td class='px-6 py-4 text-sm text-slate-900 dark:text-white font-medium'>{$job['name']}</td><td class='px-6 py-4'><span class='px-2 py-1 bg-{$statusColor}-500/10 text-{$statusColor}-500 text-xs font-bold rounded'>{$job['status']}</span></td><td class='px-6 py-4'><div class='flex items-center gap-2'><div class='w-24 h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden'><div class='h-full bg-indigo-500' style='width:{$job['progress']}%'></div></div><span class='text-xs text-slate-500'>{$job['progress']}%</span></div></td><td class='px-6 py-4 text-sm text-slate-500'>{$job['attempts']}</td><td class='px-6 py-4 text-sm text-slate-500'>{$job['created_at']}</td></tr>";
        }
        $content .= "</tbody></table></div>";
    }
    $content .= "</div><script>lucide.createIcons();</script>";
    if ($req->header('HX-Request')) {
        echo $content;
        exit;
    }
    echo View::layout('Job Queue', $content, $allMeta);
    exit;
}, [Middleware::class . '::session']);

// Relations Viewer
$router->add('GET', '/admin/session-config', function (Request $req, Response $res) use ($allMeta) {
    $sessionConfig = require __DIR__ . '/../config/session.php';
    $currentDriver = $sessionConfig['driver'];
    $drivers = ['file', 'database', 'redis'];
    
    $content = "<div class='max-w-6xl mx-auto space-y-6'><div class='mb-8'><h3 class='text-2xl font-bold text-slate-900 dark:text-white'>Session Configuration</h3><p class='text-slate-500 text-sm mt-1'>Manage session drivers and storage settings</p></div>";
    
    $content .= "<div class='bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 shadow-sm'><div class='mb-6'><h4 class='text-lg font-bold text-slate-900 dark:text-white mb-2'>Current Configuration</h4><div class='p-4 bg-slate-50 dark:bg-slate-950 rounded-lg border border-slate-200 dark:border-slate-800'><div class='flex items-center gap-3'><i data-lucide='database' class='w-5 h-5 text-indigo-500'></i><div><p class='text-sm text-slate-500'>Active Driver</p><p class='text-lg font-bold text-slate-900 dark:text-white capitalize'>{$currentDriver}</p></div></div></div></div>";
    
    $content .= "<form hx-post='/admin/session-config' hx-target='#content' hx-swap='outerHTML' class='space-y-6'><div class='space-y-4'><label class='block'><span class='text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 block'>Select Driver</span><select name='driver' id='sessionDriver' class='w-full px-4 py-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none' onchange='toggleDriverConfig(this.value)'>";
    
    foreach ($drivers as $d) {
        $selected = $d === $currentDriver ? 'selected' : '';
        $label = ucfirst($d);
        $content .= "<option value='{$d}' {$selected}>{$label}</option>";
    }
    
    $content .= "</select></label></div>";
    
    $filePath = $sessionConfig['drivers']['file']['path'] ?? '';
    $dbTable = $sessionConfig['drivers']['database']['table'] ?? 'sessions';
    $redisHost = $sessionConfig['drivers']['redis']['host'] ?? '127.0.0.1';
    $redisPort = $sessionConfig['drivers']['redis']['port'] ?? 6379;
    $redisDb = $sessionConfig['drivers']['redis']['database'] ?? 0;
    $redisPrefix = $sessionConfig['drivers']['redis']['prefix'] ?? 'sess:';
    
    $content .= "<div id='fileConfig' class='driver-config space-y-4 p-4 bg-slate-50 dark:bg-slate-950 rounded-lg border border-slate-200 dark:border-slate-800' style='display:" . ($currentDriver === 'file' ? 'block' : 'none') . "'><label class='block'><span class='text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 block'>Storage Path</span><input type='text' name='file_path' value='{$filePath}' placeholder='/storage/sessions' class='w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none'></label></div>";
    
    $content .= "<div id='databaseConfig' class='driver-config space-y-4 p-4 bg-slate-50 dark:bg-slate-950 rounded-lg border border-slate-200 dark:border-slate-800' style='display:" . ($currentDriver === 'database' ? 'block' : 'none') . "'><label class='block'><span class='text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 block'>Table Name</span><input type='text' name='db_table' value='{$dbTable}' placeholder='sessions' class='w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none'></label></div>";
    
    $content .= "<div id='redisConfig' class='driver-config space-y-4 p-4 bg-slate-50 dark:bg-slate-950 rounded-lg border border-slate-200 dark:border-slate-800' style='display:" . ($currentDriver === 'redis' ? 'block' : 'none') . "'><div class='grid grid-cols-1 md:grid-cols-2 gap-4'><label class='block'><span class='text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 block'>Host</span><input type='text' name='redis_host' value='{$redisHost}' placeholder='127.0.0.1' class='w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none'></label><label class='block'><span class='text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 block'>Port</span><input type='number' name='redis_port' value='{$redisPort}' placeholder='6379' class='w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none'></label><label class='block'><span class='text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 block'>Database</span><input type='number' name='redis_db' value='{$redisDb}' placeholder='0' class='w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none'></label><label class='block'><span class='text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 block'>Key Prefix</span><input type='text' name='redis_prefix' value='{$redisPrefix}' placeholder='sess:' class='w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none'></label></div><label class='block'><span class='text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 block'>Password (optional)</span><input type='password' name='redis_password' placeholder='Leave empty if no password' class='w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none'></label></div>";
    
    $content .= "<div class='flex gap-3 pt-4'><button type='submit' class='px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg shadow-sm transition-all flex items-center gap-2'><i data-lucide='save' class='w-4 h-4'></i> Save Configuration</button></div></form></div></div>";
    
    $content .= "<script>function toggleDriverConfig(driver){document.querySelectorAll('.driver-config').forEach(el=>el.style.display='none');if(driver==='file')document.getElementById('fileConfig').style.display='block';if(driver==='database')document.getElementById('databaseConfig').style.display='block';if(driver==='redis')document.getElementById('redisConfig').style.display='block';}lucide.createIcons();</script>";
    
    if ($req->header('HX-Request')) {
        echo $content;
        exit;
    }
    echo View::layout('Session Configuration', $content, $allMeta);
    exit;
}, [Middleware::class . '::session']);

$router->add('POST', '/admin/session-config', function (Request $req, Response $res) use ($allMeta) {
    $input = $req->input();
    $driver = $input['driver'] ?? 'file';
    
    $envPath = __DIR__ . '/../.env';
    $envContent = file_exists($envPath) ? file_get_contents($envPath) : '';
    $envLines = explode("\n", $envContent);
    $newEnvLines = [];
    $keysToUpdate = ['SESSION_DRIVER'];
    $updatedKeys = [];
    
    foreach ($envLines as $line) {
        $matched = false;
        foreach ($keysToUpdate as $key) {
            if (str_starts_with(trim($line), $key . '=')) {
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            $newEnvLines[] = $line;
        }
    }
    
    $newEnvLines[] = "SESSION_DRIVER={$driver}";
    
    if ($driver === 'file' && !empty($input['file_path'])) {
        $newEnvLines[] = "SESSION_FILE_PATH={$input['file_path']}";
    } elseif ($driver === 'database' && !empty($input['db_table'])) {
        $newEnvLines[] = "SESSION_DB_TABLE={$input['db_table']}";
    } elseif ($driver === 'redis') {
        if (!empty($input['redis_host'])) $newEnvLines[] = "SESSION_REDIS_HOST={$input['redis_host']}";
        if (!empty($input['redis_port'])) $newEnvLines[] = "SESSION_REDIS_PORT={$input['redis_port']}";
        if (!empty($input['redis_db'])) $newEnvLines[] = "SESSION_REDIS_DB={$input['redis_db']}";
        if (!empty($input['redis_prefix'])) $newEnvLines[] = "SESSION_REDIS_PREFIX={$input['redis_prefix']}";
        if (!empty($input['redis_password'])) $newEnvLines[] = "SESSION_REDIS_PASSWORD={$input['redis_password']}";
    }
    
    file_put_contents($envPath, implode("\n", $newEnvLines));
    
    $sessionConfig = require __DIR__ . '/../config/session.php';
    $currentDriver = $driver;
    $drivers = ['file', 'database', 'redis'];
    
    $responseContent = "<div class='max-w-6xl mx-auto space-y-6'><div class='mb-8'><h3 class='text-2xl font-bold text-slate-900 dark:text-white'>Session Configuration</h3><p class='text-slate-500 text-sm mt-1'>Manage session drivers and storage settings</p></div>";
    
    $responseContent .= "<div class='p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg mb-6 flex items-center gap-3'><i data-lucide='check-circle' class='w-5 h-5 text-emerald-600 dark:text-emerald-400'></i><span class='text-emerald-700 dark:text-emerald-300 font-medium'>Configuration saved to .env file. Restart required for changes to take effect.</span></div>";
    
    $responseContent .= "<div class='bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 shadow-sm'><div class='mb-6'><h4 class='text-lg font-bold text-slate-900 dark:text-white mb-2'>Current Configuration</h4><div class='p-4 bg-slate-50 dark:bg-slate-950 rounded-lg border border-slate-200 dark:border-slate-800'><div class='flex items-center gap-3'><i data-lucide='database' class='w-5 h-5 text-indigo-500'></i><div><p class='text-sm text-slate-500'>Active Driver</p><p class='text-lg font-bold text-slate-900 dark:text-white capitalize'>{$currentDriver}</p></div></div></div></div>";
    
    $responseContent .= "<form hx-post='/admin/session-config' hx-target='#content' hx-swap='outerHTML' class='space-y-6'><div class='space-y-4'><label class='block'><span class='text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 block'>Select Driver</span><select name='driver' id='sessionDriver' class='w-full px-4 py-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none' onchange='toggleDriverConfig(this.value)'>";
    
    foreach ($drivers as $d) {
        $selected = $d === $currentDriver ? 'selected' : '';
        $label = ucfirst($d);
        $responseContent .= "<option value='{$d}' {$selected}>{$label}</option>";
    }
    
    $responseContent .= "</select></label></div>";
    
    $filePath = $input['file_path'] ?? $sessionConfig['drivers']['file']['path'] ?? '';
    $dbTable = $input['db_table'] ?? $sessionConfig['drivers']['database']['table'] ?? 'sessions';
    $redisHost = $input['redis_host'] ?? $sessionConfig['drivers']['redis']['host'] ?? '127.0.0.1';
    $redisPort = $input['redis_port'] ?? $sessionConfig['drivers']['redis']['port'] ?? 6379;
    $redisDb = $input['redis_db'] ?? $sessionConfig['drivers']['redis']['database'] ?? 0;
    $redisPrefix = $input['redis_prefix'] ?? $sessionConfig['drivers']['redis']['prefix'] ?? 'sess:';
    
    $responseContent .= "<div id='fileConfig' class='driver-config space-y-4 p-4 bg-slate-50 dark:bg-slate-950 rounded-lg border border-slate-200 dark:border-slate-800' style='display:" . ($currentDriver === 'file' ? 'block' : 'none') . "'><label class='block'><span class='text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 block'>Storage Path</span><input type='text' name='file_path' value='{$filePath}' placeholder='/storage/sessions' class='w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none'></label></div>";
    
    $responseContent .= "<div id='databaseConfig' class='driver-config space-y-4 p-4 bg-slate-50 dark:bg-slate-950 rounded-lg border border-slate-200 dark:border-slate-800' style='display:" . ($currentDriver === 'database' ? 'block' : 'none') . "'><label class='block'><span class='text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 block'>Table Name</span><input type='text' name='db_table' value='{$dbTable}' placeholder='sessions' class='w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none'></label></div>";
    
    $responseContent .= "<div id='redisConfig' class='driver-config space-y-4 p-4 bg-slate-50 dark:bg-slate-950 rounded-lg border border-slate-200 dark:border-slate-800' style='display:" . ($currentDriver === 'redis' ? 'block' : 'none') . "'><div class='grid grid-cols-1 md:grid-cols-2 gap-4'><label class='block'><span class='text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 block'>Host</span><input type='text' name='redis_host' value='{$redisHost}' placeholder='127.0.0.1' class='w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none'></label><label class='block'><span class='text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 block'>Port</span><input type='number' name='redis_port' value='{$redisPort}' placeholder='6379' class='w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none'></label><label class='block'><span class='text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 block'>Database</span><input type='number' name='redis_db' value='{$redisDb}' placeholder='0' class='w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none'></label><label class='block'><span class='text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 block'>Key Prefix</span><input type='text' name='redis_prefix' value='{$redisPrefix}' placeholder='sess:' class='w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none'></label></div><label class='block'><span class='text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 block'>Password (optional)</span><input type='password' name='redis_password' placeholder='Leave empty if no password' class='w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none'></label></div>";
    
    $responseContent .= "<div class='flex gap-3 pt-4'><button type='submit' class='px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg shadow-sm transition-all flex items-center gap-2'><i data-lucide='save' class='w-4 h-4'></i> Save Configuration</button></div></form></div></div>";
    
    $responseContent .= "<script>function toggleDriverConfig(driver){document.querySelectorAll('.driver-config').forEach(el=>el.style.display='none');if(driver==='file')document.getElementById('fileConfig').style.display='block';if(driver==='database')document.getElementById('databaseConfig').style.display='block';if(driver==='redis')document.getElementById('redisConfig').style.display='block';}lucide.createIcons();</script>";
    
    if ($req->header('HX-Request')) {
        echo $responseContent;
        exit;
    }
    echo View::layout('Session Configuration', $responseContent, $allMeta);
    exit;
}, [Middleware::class . '::session']);

$router->add('GET', '/admin/relations', function (Request $req, Response $res) use ($allMeta) {
    $content = "<div class='max-w-6xl mx-auto space-y-6'><div class='mb-8'><h3 class='text-2xl font-bold text-slate-900 dark:text-white'>Table Relations</h3><p class='text-slate-500 text-sm mt-1'>Visual map of foreign key relationships</p></div>";
    $relations = [];
    foreach ($allMeta as $meta) {
        foreach ($meta['fields'] as $field) {
            if (!empty($field['references']['table'])) {
                $relations[] = [
                    'from_table' => $meta['table'],
                    'from_field' => $field['name'],
                    'to_table' => $field['references']['table'],
                    'to_field' => $field['references']['column'] ?? 'id'
                ];
            }
        }
    }
    if (empty($relations)) {
        $content .= "<div class='bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-xl p-12 text-center'><i data-lucide='git-branch' class='w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4'></i><p class='text-slate-500 font-medium'>No relations defined yet</p><p class='text-slate-400 text-sm mt-2'>Add foreign key references in your table schemas</p></div>";
    } else {
        $content .= "<div class='space-y-4'>";
        foreach ($relations as $rel) {
            $content .= "<div class='bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 flex items-center gap-6'><div class='flex-1'><div class='flex items-center gap-3'><div class='px-3 py-1.5 bg-indigo-50 dark:bg-indigo-500/10 rounded-lg'><span class='text-sm font-bold text-indigo-600 dark:text-indigo-400'>{$rel['from_table']}</span></div><i data-lucide='arrow-right' class='w-4 h-4 text-slate-400'></i><code class='text-xs text-slate-500 bg-slate-50 dark:bg-slate-950 px-2 py-1 rounded'>{$rel['from_field']}</code></div></div><i data-lucide='link' class='w-5 h-5 text-slate-300'></i><div class='flex-1'><div class='flex items-center gap-3'><code class='text-xs text-slate-500 bg-slate-50 dark:bg-slate-950 px-2 py-1 rounded'>{$rel['to_field']}</code><i data-lucide='arrow-right' class='w-4 h-4 text-slate-400'></i><div class='px-3 py-1.5 bg-emerald-50 dark:bg-emerald-500/10 rounded-lg'><span class='text-sm font-bold text-emerald-600 dark:text-emerald-400'>{$rel['to_table']}</span></div></div></div></div>";
        }
        $content .= "</div>";
    }
    $content .= "</div><script>lucide.createIcons();</script>";
    if ($req->header('HX-Request')) {
        echo $content;
        exit;
    }
    echo View::layout('Relations', $content, $allMeta);
    exit;
}, [Middleware::class . '::session']);

// API Explorer
$router->add('GET', '/admin/api-explorer', function (Request $req, Response $res) use ($allMeta, $apiPolicy) {
    $content = "<div class='max-w-7xl mx-auto space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500'><div class='mb-8'><div class='flex flex-col md:flex-row md:items-center justify-between gap-4'><div><h3 class='text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight'>API Explorer</h3><p class='text-slate-500 text-sm mt-2'>Interactive documentation, live testing, and endpoint status</p></div><a hx-get='/admin/api-settings' hx-target='#content' hx-push-url='true' class='inline-flex items-center gap-2 px-5 py-2.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-sm font-bold rounded-xl shadow-lg hover:shadow-xl transition-all hover:-translate-y-0.5 cursor-pointer'><i data-lucide='settings' class='w-4 h-4'></i> Manage Policy</a></div></div>";
    $content .= "<div class='bg-white dark:bg-slate-900/80 backdrop-blur-xl border border-slate-200/60 dark:border-slate-800/60 rounded-2xl p-6 shadow-sm space-y-5'><div class='flex items-center gap-3'><div class='p-2 bg-indigo-50 dark:bg-indigo-500/10 rounded-lg text-indigo-600 dark:text-indigo-400'><i data-lucide='shield-check' class='w-5 h-5'></i></div><h4 class='font-bold text-slate-900 dark:text-white text-lg'>Authentication Test</h4></div><p class='text-sm text-slate-500'>Register or login to receive a JWT. Endpoints marked with <span class='px-2 py-0.5 bg-amber-500/10 text-amber-500 text-[10px] font-bold rounded'>JWT</span> require the <code>Authorization: Bearer &lt;token&gt;</code> header.</p><div class='grid grid-cols-1 lg:grid-cols-2 gap-6'><div class='space-y-3 bg-slate-50/50 dark:bg-slate-800/20 p-5 rounded-xl border border-slate-100 dark:border-slate-800/50'><h5 class='text-sm font-bold flex items-center gap-2'><i data-lucide='user-plus' class='w-4 h-4 text-slate-400'></i> Register Test</h5><textarea id='auth-register-body' rows='3' class='w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-3 text-xs font-mono focus:ring-2 focus:ring-emerald-500 outline-none transition-all'>{\"username\":\"demo\",\"password\":\"password123\"}</textarea><button onclick=\"authTest('/register','auth-register-body','auth-register-result')\" class='w-full px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold rounded-lg transition-all shadow-sm'>Send Register</button><pre id='auth-register-result' class='hidden p-4 bg-slate-950 text-emerald-400 rounded-lg text-xs overflow-auto border border-emerald-900/50'></pre></div><div class='space-y-3 bg-slate-50/50 dark:bg-slate-800/20 p-5 rounded-xl border border-slate-100 dark:border-slate-800/50'><h5 class='text-sm font-bold flex items-center gap-2'><i data-lucide='log-in' class='w-4 h-4 text-slate-400'></i> Login Test</h5><textarea id='auth-login-body' rows='3' class='w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-3 text-xs font-mono focus:ring-2 focus:ring-indigo-500 outline-none transition-all'>{\"username\":\"demo\",\"password\":\"password123\"}</textarea><button onclick=\"authTest('/login','auth-login-body','auth-login-result')\" class='w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-lg transition-all shadow-sm'>Send Login</button><pre id='auth-login-result' class='hidden p-4 bg-slate-950 text-indigo-400 rounded-lg text-xs overflow-auto border border-indigo-900/50'></pre></div></div></div>";
    foreach ($allMeta as $meta) {
        $table = $meta['table'];
        $cfg = $apiPolicy->tableConfig($table);
        $tableEnabled = $apiPolicy->isTableEnabled($table);
        $tableBadge = $tableEnabled ? "<span class='px-2.5 py-1 bg-emerald-500/10 text-emerald-500 text-xs font-bold rounded-full flex items-center gap-1.5 border border-emerald-500/20'><i data-lucide='check-circle-2' class='w-3 h-3'></i> ENABLED</span>" : "<span class='px-2.5 py-1 bg-red-500/10 text-red-500 text-xs font-bold rounded-full flex items-center gap-1.5 border border-red-500/20'><i data-lucide='x-circle' class='w-3 h-3'></i> DISABLED</span>";
        $fields = array_filter($meta['fields'], fn($f) => !in_array($f['name'], ['id', 'created_at', 'updated_at']));
        $samplePost = [];
        foreach ($fields as $f) {
            $samplePost[$f['name']] = match (strtoupper($f['type'] ?? 'TEXT')) {
                'INTEGER', 'BIGINT', 'SMALLINT', 'TINYINT' => 0,
                'REAL', 'FLOAT', 'DOUBLE', 'DECIMAL' => 0.0,
                'BOOLEAN' => false,
                'JSON' => ['key' => 'value'],
                default => 'sample'
            };
        }
        $sampleJson = e(json_encode($samplePost, JSON_PRETTY_PRINT));
        $content .= "<div class='bg-white dark:bg-slate-900/80 backdrop-blur-xl border border-slate-200/60 dark:border-slate-800/60 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow'><div class='bg-slate-50/80 dark:bg-slate-800/40 px-6 py-5 border-b border-slate-200/60 dark:border-slate-800/60 flex flex-col md:flex-row md:items-center justify-between gap-4'><div><div class='flex items-center gap-3 mb-1'><h4 class='text-xl font-bold capitalize text-slate-900 dark:text-white flex items-center gap-2'><i data-lucide='database' class='w-5 h-5 text-indigo-500'></i> {$table}</h4>{$tableBadge}</div><p class='text-xs text-slate-500'>All REST endpoints for this resource.</p></div><div class='px-3 py-1.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 text-xs font-mono text-slate-500'>/api/{$table}</div></div><div class='divide-y divide-slate-100 dark:divide-slate-800/50'>";
        $endpoints = [
            ['list', 'GET', 'emerald', "/api/{$table}", 'List records', '', false],
            ['create', 'POST', 'sky', "/api/{$table}", 'Create record', $sampleJson, true],
            ['detail', 'GET', 'emerald', "/api/{$table}/{id}", 'Get by ID', '', false],
            ['update', 'PUT', 'amber', "/api/{$table}/{id}", 'Update record', $sampleJson, true],
            ['delete', 'DELETE', 'rose', "/api/{$table}/{id}", 'Delete record', '', false]
        ];
        foreach ($endpoints as $idx => [$epName, $method, $color, $path, $desc, $body, $hasBody]) {
            $epEnabled = $tableEnabled && $apiPolicy->isEndpointEnabled($table, $epName);
            $requiresAuth = $apiPolicy->endpointRequiresAuth($table, $epName);
            $roles = $apiPolicy->endpointRoles($table, $epName);
            $rid = "{$table}_{$idx}";
            $hasParam = str_contains($path, '{id}');
            $defaultHeaders = $requiresAuth ? '{"Authorization":"Bearer YOUR_TOKEN"}' : '{}';
            $authBadge = $requiresAuth ? "<span class='px-2 py-0.5 bg-amber-500/10 text-amber-500 border border-amber-500/20 text-[10px] font-bold rounded flex items-center gap-1'><i data-lucide='lock' class='w-3 h-3'></i> JWT</span>" : "<span class='px-2 py-0.5 bg-sky-500/10 text-sky-500 border border-sky-500/20 text-[10px] font-bold rounded flex items-center gap-1'><i data-lucide='globe' class='w-3 h-3'></i> PUBLIC</span>";
            $enabledBadge = $epEnabled ? "<span class='px-2 py-0.5 bg-emerald-500/10 text-emerald-500 text-[10px] font-bold rounded'>ON</span>" : "<span class='px-2 py-0.5 bg-rose-500/10 text-rose-500 text-[10px] font-bold rounded'>OFF</span>";
            $epLabel = strtoupper($epName);
            $rolesText = $roles ? 'roles: ' . e(implode(',', $roles)) : 'no role limit';
            $content .= "<div class='" . (!$epEnabled ? 'opacity-50 grayscale hover:grayscale-0 transition-all' : '') . "'><button onclick=\"toggleAccordion('{$rid}')\" class='w-full px-6 py-4 flex items-center gap-4 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors text-left group'><span class='px-3 py-1.5 bg-{$color}-500/10 text-{$color}-600 dark:text-{$color}-400 text-xs font-bold rounded-lg min-w-[70px] text-center border border-{$color}-500/20'>{$method}</span><code class='text-sm text-slate-700 dark:text-slate-300 font-mono flex-1 group-hover:text-{$color}-600 dark:group-hover:text-{$color}-400 transition-colors'>{$path}</code><div class='hidden md:flex items-center gap-2'><span class='px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-500 text-[10px] font-bold rounded'>{$epLabel}</span>{$enabledBadge}{$authBadge}<span class='text-[10px] text-slate-400 uppercase tracking-wider font-semibold w-24 text-right truncate'>{$rolesText}</span></div><i id='icon-{$rid}' data-lucide='chevron-down' class='w-5 h-5 text-slate-400 transition-transform duration-300'></i></button><div id='content-{$rid}' class='hidden px-6 pb-6 pt-2'><div class='pl-[86px] space-y-4'><div class='md:hidden flex items-center gap-2 mb-4'><span class='px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-500 text-[10px] font-bold rounded'>{$epLabel}</span>{$enabledBadge}{$authBadge}<span class='text-[10px] text-slate-400 uppercase tracking-wider font-semibold'>{$rolesText}</span></div><p class='text-sm text-slate-500 flex items-center gap-2'><i data-lucide='info' class='w-4 h-4'></i> {$desc}</p><div class='grid grid-cols-1 lg:grid-cols-2 gap-6'>";
            if ($hasParam) $content .= "<div class='lg:col-span-2'><label class='block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase mb-2 flex items-center gap-1.5'><i data-lucide='variable' class='w-3.5 h-3.5'></i> Path Parameter (ID)</label><input id='param-{$rid}' type='text' value='1' class='w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-3 text-sm font-mono focus:ring-2 focus:ring-{$color}-500 outline-none transition-all'></div>";
            $content .= "<div><label class='block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase mb-2 flex items-center gap-1.5'><i data-lucide='heading' class='w-3.5 h-3.5'></i> Headers JSON</label><textarea id='headers-{$rid}' rows='" . ($hasBody ? '6' : '3') . "' class='w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-3 text-xs font-mono focus:ring-2 focus:ring-{$color}-500 outline-none transition-all resize-none'>{$defaultHeaders}</textarea></div>";
            if ($hasBody) $content .= "<div><label class='block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase mb-2 flex items-center gap-1.5'><i data-lucide='file-json' class='w-3.5 h-3.5'></i> Body JSON</label><textarea id='body-{$rid}' rows='6' class='w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-3 text-xs font-mono focus:ring-2 focus:ring-{$color}-500 outline-none transition-all resize-none'>{$body}</textarea></div>";
            $content .= "<div class='lg:col-span-" . ($hasBody ? '2' : '1') . " flex items-end justify-end pt-2'><button onclick=\"testAPI('{$method}','{$path}','{$rid}'," . ($hasBody ? 'true' : 'false') . "," . ($hasParam ? 'true' : 'false') . ")\" class='px-6 py-2.5 bg-{$color}-600 hover:bg-{$color}-500 text-white text-sm font-bold rounded-xl shadow-sm hover:shadow-md transition-all flex items-center gap-2'><i data-lucide='play' class='w-4 h-4'></i> Send Request</button></div></div><div id='result-{$rid}' class='hidden mt-4 space-y-3 p-4 bg-slate-950 rounded-xl border border-slate-800/60'><div class='flex items-center gap-2'><div class='w-2 h-2 rounded-full bg-emerald-500 animate-pulse'></div><span id='status-{$rid}' class='text-xs font-bold font-mono text-slate-300'></span></div><pre id='response-{$rid}' class='text-emerald-400 text-xs overflow-auto max-h-96 whitespace-pre-wrap font-mono'></pre></div></div></div></div>";
        }
        $content .= "</div></div>";
    }
    $content .= "</div><script>lucide.createIcons();function toggleAccordion(rid){const c=document.getElementById('content-'+rid),i=document.getElementById('icon-'+rid),h=c.classList.contains('hidden');c.classList.toggle('hidden');i.style.transform=h?'rotate(180deg)':'rotate(0deg)';}async function authTest(path,bodyId,resultId){const o=document.getElementById(resultId);o.classList.remove('hidden');o.textContent='Loading...';try{const body=document.getElementById(bodyId).value;JSON.parse(body);const res=await fetch(path,{method:'POST',headers:{'Content-Type':'application/json'},body});const data=await res.json();o.textContent=JSON.stringify(data,null,2);if(data.token){localStorage.apiToken=data.token;document.querySelectorAll('textarea[id^=headers-]').forEach(t=>{try{const h=JSON.parse(t.value||'{}');h.Authorization='Bearer '+data.token;t.value=JSON.stringify(h,null,2);}catch(e){}});}}catch(e){o.textContent='Error: '+e.message;}}async function testAPI(method,path,rid,hasBody,hasParam){const r=document.getElementById('result-'+rid),o=document.getElementById('response-'+rid),s=document.getElementById('status-'+rid);r.classList.remove('hidden');o.textContent='Loading...';s.textContent='Connecting...';let finalPath=path;if(hasParam){finalPath=path.replace('{id}',document.getElementById('param-'+rid).value||'1');}const opts={method};try{opts.headers={'Content-Type':'application/json',...JSON.parse(document.getElementById('headers-'+rid).value||'{}')};if(localStorage.apiToken&&!opts.headers.Authorization){opts.headers.Authorization='Bearer '+localStorage.apiToken;}if(hasBody){const b=document.getElementById('body-'+rid).value;JSON.parse(b);opts.body=b;}}catch(e){o.textContent='Invalid JSON: '+e.message;s.textContent='Error';return;}try{const t=Date.now();const res=await fetch(finalPath,opts);const ct=res.headers.get('content-type');const data=ct&&ct.includes('json')?await res.json():await res.text();const ms=Date.now()-t;const color=res.ok?'text-emerald-400':'text-rose-400';s.innerHTML=`<span class='\${color}'>\${res.status} \${res.statusText}</span> <span class='text-slate-500'>| \${ms}ms</span>`;o.className=`text-xs overflow-auto max-h-96 whitespace-pre-wrap font-mono \${color}`;o.textContent=typeof data==='string'?data:JSON.stringify(data,null,2);}catch(e){o.textContent='Error: '+e.message;s.textContent='Network Error';}}</script>";
    if ($req->header('HX-Request')) { echo $content; exit; }
    echo View::layout('API Explorer', $content, $allMeta); exit;
}, [Middleware::class . '::session']);

// API Settings
$renderPolicyForm = function(ApiPolicy $policy, array $allMeta, string $banner = ''): string {
    $defaults = $policy->toArray()['defaults'] ?? [];
    $content = $banner ? "<div id='content' class='flex-1 overflow-y-auto p-8 animate-in fade-in duration-300'>" : '';
    $content .= "<div class='max-w-7xl mx-auto space-y-8'>";
    if ($banner) $content .= "<div class='p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-xl text-sm font-bold flex items-center gap-3'><i data-lucide='check-circle' class='w-5 h-5'></i> {$banner}</div>";
    $content .= "<div class='flex flex-col md:flex-row md:items-center justify-between gap-4'><div><h3 class='text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight'>API Policy</h3><p class='text-slate-500 text-sm mt-2'>Configure access control, rate limits, and endpoint availability.</p></div><a hx-get='/admin/api-explorer' hx-target='#content' hx-push-url='true' class='inline-flex items-center gap-2 px-5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-indigo-500/50 text-slate-700 dark:text-slate-300 text-sm font-bold rounded-xl shadow-sm hover:shadow-md transition-all cursor-pointer'><i data-lucide='compass' class='w-4 h-4 text-indigo-500'></i> View Explorer</a></div><form method='POST' action='/admin/api-settings' hx-post='/admin/api-settings' hx-target='#content' hx-swap='outerHTML' class='space-y-6'>";
    $content .= "<div class='bg-white dark:bg-slate-900/80 backdrop-blur-xl border border-slate-200/60 dark:border-slate-800/60 rounded-2xl p-6 md:p-8 shadow-sm'><div class='flex items-center gap-3 mb-6'><div class='p-2 bg-rose-50 dark:bg-rose-500/10 rounded-lg text-rose-600 dark:text-rose-400'><i data-lucide='sliders-horizontal' class='w-5 h-5'></i></div><div><h4 class='font-bold text-slate-900 dark:text-white text-lg'>Global Defaults</h4><p class='text-xs text-slate-500 mt-0.5'>Applied to new tables or endpoints without explicit overrides.</p></div></div><div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6'><label class='flex items-center gap-3 p-4 border border-slate-200 dark:border-slate-800 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors cursor-pointer'><div class='relative flex items-center'><input type='checkbox' name='defaults_enabled' class='peer sr-only' " . (!empty($defaults['enabled']) ? 'checked' : '') . "><div class='w-9 h-5 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[\"\"] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500'></div></div><span class='text-sm font-bold text-slate-700 dark:text-slate-300'>New Tables Enabled</span></label><label class='flex items-center gap-3 p-4 border border-slate-200 dark:border-slate-800 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors cursor-pointer'><div class='relative flex items-center'><input type='checkbox' name='defaults_auth' class='peer sr-only' " . (!empty($defaults['auth']) ? 'checked' : '') . "><div class='w-9 h-5 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[\"\"] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-500'></div></div><span class='text-sm font-bold text-slate-700 dark:text-slate-300'>Require JWT Auth</span></label><div><label class='block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2'>Default Roles</label><input name='defaults_roles' value='" . e(implode(',', $defaults['roles'] ?? [])) . "' placeholder='e.g., admin, user' class='w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-slate-900 dark:text-white'></div><div><label class='block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2'>Max List Limit</label><input name='defaults_max_limit' value='" . e($defaults['max_limit'] ?? 100) . "' type='number' min='1' max='1000' class='w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-slate-900 dark:text-white'></div></div></div>";
    $epRows = [
        ['list',   'GET',    '/api/{t}', 'emerald'],
        ['detail', 'GET',    '/api/{t}/{id}', 'emerald'],
        ['create', 'POST',   '/api/{t}', 'sky'],
        ['update', 'PUT',    '/api/{t}/{id}', 'amber'],
        ['delete', 'DELETE', '/api/{t}/{id}', 'rose'],
    ];
    foreach ($allMeta as $meta) {
        $table = $meta['table'];
        $cfg = $policy->tableConfig($table);
        $content .= "<div class='bg-white dark:bg-slate-900/80 backdrop-blur-xl border border-slate-200/60 dark:border-slate-800/60 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow'><div class='px-6 py-5 bg-slate-50/80 dark:bg-slate-800/40 flex flex-col md:flex-row md:items-center justify-between border-b border-slate-200/60 dark:border-slate-800/60 gap-4'><div><h4 class='font-bold text-lg text-slate-900 dark:text-white capitalize flex items-center gap-2'><i data-lucide='database' class='w-4 h-4 text-indigo-500'></i> {$table}</h4><p class='text-xs text-slate-500 mt-1 font-mono'>/api/{$table}</p></div><label class='flex items-center gap-3 cursor-pointer'><span class='text-sm font-bold text-slate-600 dark:text-slate-400'>Master Switch</span><div class='relative flex items-center'><input type='checkbox' name='enabled[{$table}]' class='peer sr-only' " . (!empty($cfg['enabled']) ? 'checked' : '') . "><div class='w-10 h-5.5 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[\"\"] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4.5 after:w-4.5 after:transition-all peer-checked:bg-emerald-500'></div></div></label></div><div class='p-0 overflow-x-auto'><table class='w-full text-sm'><thead class='bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-800'><tr class='text-left text-xs text-slate-500 uppercase tracking-wider'><th class='py-4 px-6 font-semibold'>Endpoint Route</th><th class='py-4 px-6 font-semibold text-center'>Enabled</th><th class='py-4 px-6 font-semibold text-center'>Req JWT</th><th class='py-4 px-6 font-semibold'>Role Overrides</th></tr></thead><tbody class='divide-y divide-slate-100 dark:divide-slate-800/50'>";
        foreach ($epRows as [$ep, $method, $pathTpl, $color]) {
            $path = str_replace('{t}', $table, $pathTpl);
            $epLabel = strtoupper($ep);
            $roles = e(implode(',', $policy->endpointRoles($table, $ep)));
            $content .= "<tr class='hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors'><td class='py-4 px-6'><div class='flex items-center gap-3'><span class='px-2.5 py-1 bg-{$color}-500/10 text-{$color}-600 dark:text-{$color}-400 text-[10px] font-bold rounded-lg border border-{$color}-500/20 w-16 text-center'>{$method}</span><div><code class='text-xs text-slate-700 dark:text-slate-300 block mb-1'>{$path}</code><span class='text-[10px] text-slate-400 font-semibold uppercase tracking-wider'>{$epLabel}</span></div></div></td><td class='py-4 px-6 text-center'><div class='flex justify-center'><input type='checkbox' name='endpoints[{$table}][{$ep}][enabled]' class='w-4 h-4 text-emerald-500 bg-slate-100 border-slate-300 rounded focus:ring-emerald-500 dark:focus:ring-emerald-600 dark:ring-offset-slate-800 focus:ring-2 dark:bg-slate-700 dark:border-slate-600 transition-all cursor-pointer' " . ($policy->isEndpointEnabled($table, $ep) ? 'checked' : '') . "></div></td><td class='py-4 px-6 text-center'><div class='flex justify-center'><input type='checkbox' name='endpoints[{$table}][{$ep}][auth]' class='w-4 h-4 text-indigo-500 bg-slate-100 border-slate-300 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:ring-offset-slate-800 focus:ring-2 dark:bg-slate-700 dark:border-slate-600 transition-all cursor-pointer' " . ($policy->endpointRequiresAuth($table, $ep) ? 'checked' : '') . "></div></td><td class='py-4 px-6'><input name='endpoints[{$table}][{$ep}][roles]' value='{$roles}' placeholder='admin, moderator' class='w-full max-w-xs px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-xs focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-slate-900 dark:text-white'></td></tr>";
        }
        $content .= "</tbody></table></div><div class='px-6 py-4 bg-slate-50/50 dark:bg-slate-800/20 border-t border-slate-200/60 dark:border-slate-800/60 flex items-center justify-between'><div class='flex items-center gap-3'><i data-lucide='layers' class='w-4 h-4 text-slate-400'></i><span class='text-sm font-bold text-slate-600 dark:text-slate-400'>Pagination Limit</span></div><div class='flex items-center gap-2'><input name='max_limit[{$table}]' value='" . e($cfg['max_limit'] ?? 100) . "' type='number' min='1' max='1000' class='w-24 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-xs text-center focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-slate-900 dark:text-white'><span class='text-xs text-slate-500'>records/page max</span></div></div></div>";
    }
    $content .= "<div class='sticky bottom-6 flex justify-end'><button class='px-8 py-3.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-xl shadow-lg hover:shadow-indigo-500/25 transition-all flex items-center gap-2 hover:-translate-y-0.5'><i data-lucide='save' class='w-4 h-4'></i> Save API Policy</button></div></form></div>";
    if ($banner) $content .= "</div><script>lucide.createIcons();</script>";
    return $content;
};

$router->add('GET', '/admin/api-settings', function (Request $req, Response $res) use ($allMeta, $renderPolicyForm) {
    $apiPolicy = ApiPolicy::fromFile(__DIR__ . '/../config/api.json');
    $content = $renderPolicyForm($apiPolicy, $allMeta);
    if ($req->header('HX-Request')) { echo $content; exit; }
    echo View::layout('API Policy', $content, $allMeta); exit;
}, [Middleware::class . '::session']);

$router->add('POST', '/admin/api-settings', function (Request $req, Response $res) use ($allMeta, $renderPolicyForm) {
    $input = $req->input();
    $defaults = [
        'enabled' => isset($input['defaults_enabled']),
        'auth' => isset($input['defaults_auth']),
        'roles' => array_values(array_filter(array_map('trim', explode(',', $input['defaults_roles'] ?? '')))),
        'max_limit' => max(1, min(1000, (int)($input['defaults_max_limit'] ?? 100))),
        'endpoints' => [],
    ];
    $tables = [];
    foreach ($allMeta as $meta) {
        $table = $meta['table'];
        $endpoints = [];
        foreach (ApiPolicy::ENDPOINTS as $ep) {
            $epInput = $input['endpoints'][$table][$ep] ?? [];
            $endpoints[$ep] = [
                'enabled' => isset($epInput['enabled']),
                'auth' => isset($epInput['auth']),
                'roles' => array_values(array_filter(array_map('trim', explode(',', $epInput['roles'] ?? '')))),
            ];
        }
        $tables[$table] = [
            'enabled' => isset($input['enabled'][$table]),
            'auth' => true,
            'roles' => [],
            'endpoints' => $endpoints,
            'max_limit' => max(1, min(1000, (int)($input['max_limit'][$table] ?? 100))),
        ];
    }
    $savedConfig = ['defaults' => $defaults, 'tables' => $tables];
    ApiPolicy::saveJson(__DIR__ . '/../config/api.json', $savedConfig);
    if ($req->header('HX-Request')) {
        $freshPolicy = ApiPolicy::fromFile(__DIR__ . '/../config/api.json');
        echo $renderPolicyForm($freshPolicy, $allMeta, 'API policy saved.');
        exit;
    }
    header('Location: /admin/api-settings'); exit;
}, [Middleware::class . '::session']);

// New Schema Form
$router->add('GET', '/admin/schema/new', function (Request $req, Response $res) use ($allMeta) {
    $content = View::schemaForm();
    if ($req->header('HX-Request')) {
        echo $content;
        exit;
    }
    echo View::layout('Create Table', $content, $allMeta);
    exit;
}, [Middleware::class . '::session']);

// Save Schema and Sync
$router->add('POST', '/admin/schema', function (Request $req, Response $res) {
    $input = $req->input();
    $name = basename($input['table']);
    $fields = [];
    foreach ($input['field_names'] as $i => $fieldName) {
        if (empty(trim($fieldName)))
            continue;
        $fields[] = [
            'name' => $fieldName,
            'type' => $input['field_types'][$i] ?? 'TEXT',
            'length' => $input['field_lengths'][$i] ?? '',
            'default' => $input['field_defaults'][$i] ?? '',
            'nullable' => empty($input['field_notnull'][$i]),
            'unique' => !empty($input['field_unique'][$i]),
            'primary' => !empty($input['field_primary'][$i]),
            'autoincrement' => !empty($input['field_autoinc'][$i]),
            'references' => [
                'table' => $input['field_refs_table'][$i] ?? '',
                'column' => $input['field_refs_col'][$i] ?? 'id',
            ],
        ];
    }
    if (!empty($input['with_timestamps'])) {
        $fields[] = ['name' => 'created_at', 'type' => 'DATETIME', 'default' => 'CURRENT_TIMESTAMP', 'nullable' => true, 'references' => ['table' => '', 'column' => '']];
        $fields[] = ['name' => 'updated_at', 'type' => 'DATETIME', 'default' => 'CURRENT_TIMESTAMP', 'nullable' => true, 'references' => ['table' => '', 'column' => '']];
    }
    $indexes = [];
    if (!empty($input['index_names'])) {
        foreach ($input['index_names'] as $idx => $indexName) {
            if (empty(trim($indexName)))
                continue;
            $cols = array_filter(array_map('trim', explode(',', $input['index_columns'][$idx] ?? '')));
            if (empty($cols))
                continue;
            $indexes[] = [
                'name' => $indexName,
                'columns' => $cols,
                'unique' => !empty($input['index_unique'][$idx])
            ];
        }
    }
    $fillable = array_filter(array_map('trim', explode(',', $input['fillable'] ?? '')));
    $hidden = array_filter(array_map('trim', explode(',', $input['hidden'] ?? '')));
    $casts = [];
    if (!empty($input['casts'])) {
        $castsJson = json_decode($input['casts'], true);
        if (is_array($castsJson)) {
            $casts = $castsJson;
        }
    }
    $meta = [
        'table' => $name,
        'fillable' => $fillable,
        'hidden' => $hidden,
        'casts' => $casts,
        'indexes' => $indexes,
        'fields' => $fields
    ];
    file_put_contents(__DIR__ . "/../metadata/{$name}.json", json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    Migration::sync($meta);
    if ($req->header('HX-Request')) {
        header("HX-Redirect: /admin/{$name}");
        exit;
    }
    header('Location: /admin/' . $name);
    exit;
}, [Middleware::class . '::session']);

// Quick Sync Schema
$router->add('POST', '/admin/schema/sync/{table}', function (Request $req, Response $res, array $params) {
    $table = $params['table'];
    $meta = Metadata::load($table);
    Migration::sync($meta);
    if ($req->header('HX-Request')) {
        header("HX-Refresh: true");
        echo "";
        exit;
    }
    header('Location: /admin/' . $table);
    exit;
}, [Middleware::class . '::session']);

// Edit Schema Form
$router->add('GET', '/admin/schema/edit/{table}', function (Request $req, Response $res, array $params) use ($allMeta) {
    $meta = Metadata::load($params['table']);
    $content = View::schemaForm($meta);
    if ($req->header('HX-Request')) {
        echo $content;
        exit;
    }
    echo View::layout("Edit {$params['table']}", $content, $allMeta);
    exit;
}, [Middleware::class . '::session']);

// Update Schema
$router->add('POST', '/admin/schema/edit/{table}', function (Request $req, Response $res) {
    $input = $req->input();
    $name = basename($input['table']);
    $fields = [];
    foreach ($input['field_names'] as $i => $fieldName) {
        if (empty(trim($fieldName)))
            continue;
        $fields[] = [
            'name' => $fieldName,
            'type' => $input['field_types'][$i] ?? 'TEXT',
            'length' => $input['field_lengths'][$i] ?? '',
            'default' => $input['field_defaults'][$i] ?? '',
            'nullable' => empty($input['field_notnull'][$i]),
            'unique' => !empty($input['field_unique'][$i]),
            'primary' => !empty($input['field_primary'][$i]),
            'autoincrement' => !empty($input['field_autoinc'][$i]),
            'references' => [
                'table' => $input['field_refs_table'][$i] ?? '',
                'column' => $input['field_refs_col'][$i] ?? 'id',
            ],
        ];
    }
    if (!empty($input['with_timestamps'])) {
        $fields[] = ['name' => 'created_at', 'type' => 'DATETIME', 'default' => 'CURRENT_TIMESTAMP', 'nullable' => true, 'references' => ['table' => '', 'column' => '']];
        $fields[] = ['name' => 'updated_at', 'type' => 'DATETIME', 'default' => 'CURRENT_TIMESTAMP', 'nullable' => true, 'references' => ['table' => '', 'column' => '']];
    }
    $indexes = [];
    if (!empty($input['index_names'])) {
        foreach ($input['index_names'] as $idx => $indexName) {
            if (empty(trim($indexName)))
                continue;
            $cols = array_filter(array_map('trim', explode(',', $input['index_columns'][$idx] ?? '')));
            if (empty($cols))
                continue;
            $indexes[] = [
                'name' => $indexName,
                'columns' => $cols,
                'unique' => !empty($input['index_unique'][$idx])
            ];
        }
    }
    $fillable = array_filter(array_map('trim', explode(',', $input['fillable'] ?? '')));
    $hidden = array_filter(array_map('trim', explode(',', $input['hidden'] ?? '')));
    $casts = [];
    if (!empty($input['casts'])) {
        $castsJson = json_decode($input['casts'], true);
        if (is_array($castsJson)) {
            $casts = $castsJson;
        }
    }
    $meta = [
        'table' => $name,
        'fillable' => $fillable,
        'hidden' => $hidden,
        'casts' => $casts,
        'indexes' => $indexes,
        'fields' => $fields
    ];
    file_put_contents(__DIR__ . "/../metadata/{$name}.json", json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    Migration::sync($meta);
    if ($req->header('HX-Request')) {
        header("HX-Redirect: /admin/{$name}");
        exit;
    }
    header('Location: /admin/' . $name);
    exit;
}, [Middleware::class . '::session']);

$router->add('GET', '/admin/{table}/export', function (Request $req, Response $res, array $params) {
    $table = basename($params['table']);
    $format = $req->query('format') ?? 'csv';
    $meta = Metadata::load($table);
    $data = DB::query("SELECT * FROM {$table}");
    if ($format === 'json') {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $table . '_' . date('Y-m-d') . '.json"');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $table . '_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]), ',', '"', '\\');
        foreach ($data as $row) {
            fputcsv($output, $row, ',', '"', '\\');
        }
    }
    fclose($output);
    exit;
}, [Middleware::class . '::session']);

$router->add('POST', '/admin/{table}/bulk-delete', function (Request $req, Response $res, array $params) {
    $table = basename($params['table']);
    $input = $req->input();
    $ids = $input['ids'] ?? [];
    if (empty($ids) || !is_array($ids)) {
        $res->json(['error' => 'No IDs provided'], 400);
        return;
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    DB::query("DELETE FROM `{$table}` WHERE id IN ({$placeholders})", $ids);
    $res->json(['success' => true, 'deleted' => count($ids)]);
}, [Middleware::class . '::session']);

// Delete Table
$router->add('DELETE', '/admin/schema/{table}', function (Request $req, Response $res, array $params) {
    $table = basename($params['table']);
    $protected = ['users', 'job_workers'];
    if (in_array($table, $protected)) {
        $res->json(['error' => 'Table ' . $table . ' is protected and cannot be deleted.'], 403);
        return;
    }
    @unlink(__DIR__ . "/../metadata/{$table}.json");
    DB::query("DROP TABLE IF EXISTS {$table}");
    header('HX-Redirect: /admin');
    exit;
}, [Middleware::class . '::session']);

$router->add('GET', '/', function (Request $req, Response $res) {
    $res->json(['status' => 'online', 'engine' => 'Nexph']);
});

$ms = round(
    (microtime(true) - $GLOBALS['__nexph_start']) * 1000,
    2
);

file_put_contents(
    sys_get_temp_dir() . '/nexph_ms',
    $ms
);

