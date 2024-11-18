<?php

function isProduction() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return $host === 'llmstxt.directory';
}

function isStaging() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return $host === 'staging.llmstxt.directory';
}
