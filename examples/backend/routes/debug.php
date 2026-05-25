<?php

use Core\Http\Request;
use Core\Http\Response;
use Core\Database\QueryLogger;
use Core\Auth\SessionGuard;

$router->add('GET', '/debug/queries', function(Request $req, Response $res) {
    
    $queries = QueryLogger::getQueries();
    $totalTime = QueryLogger::getTotalTime();
    $count = QueryLogger::getCount();
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <title>Query Debug - Nexph</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #1e1e1e; color: #d4d4d4; }
        .summary { background: #252526; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .query { background: #252526; padding: 15px; margin-bottom: 10px; border-radius: 5px; border-left: 3px solid #007acc; }
        .sql { color: #ce9178; margin: 10px 0; }
        .params { color: #4ec9b0; font-size: 12px; }
        .time { color: #b5cea8; float: right; }
        .slow { border-left-color: #f48771; }
        h1 { color: #4ec9b0; }
        h2 { color: #569cd6; font-size: 14px; }
    </style>
</head>
<body>
    <h1>Query Debug Log</h1>
    <div class="summary">
        <strong>Total Queries:</strong> ' . $count . '<br>
        <strong>Total Time:</strong> ' . round($totalTime * 1000, 2) . 'ms<br>
        <strong>Average:</strong> ' . ($count > 0 ? round(($totalTime / $count) * 1000, 2) : 0) . 'ms
    </div>';
    
    foreach ($queries as $i => $q) {
        $timeMs = round($q['time'] * 1000, 2);
        $slow = $timeMs > 10 ? ' slow' : '';
        $html .= '<div class="query' . $slow . '">
            <span class="time">' . $timeMs . 'ms</span>
            <h2>Query #' . ($i + 1) . '</h2>
            <div class="sql">' . htmlspecialchars($q['sql']) . '</div>';
        
        if (!empty($q['params'])) {
            $html .= '<div class="params">Params: ' . htmlspecialchars(json_encode($q['params'])) . '</div>';
        }
        
        $html .= '</div>';
    }
    
    $html .= '</body></html>';
    
    $res->html($html);
});
