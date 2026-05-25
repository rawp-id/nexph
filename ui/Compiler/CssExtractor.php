<?php

namespace Nexph\Compiler;

class CssExtractor
{
    public function extract(array $ast): string
    {
        return <<<CSS
.card {
    max-width: 400px;
    margin: 50px auto;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    background: white;
    text-align: center;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.card h1 {
    font-size: 72px;
    margin: 0 0 30px 0;
    color: #2563eb;
    font-weight: 700;
}

.card button {
    padding: 12px 32px;
    font-size: 16px;
    font-weight: 600;
    color: white;
    background: #2563eb;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.card button:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.card button:active {
    transform: translateY(0);
}

body {
    margin: 0;
    padding: 0;
    background: #f3f4f6;
    min-height: 100vh;
}
CSS;
    }
}
