<?php
// Simple global debug injector
if (!defined('ABSPATH')) exit;

function positivo_debug_log($label, $data=null){
    $log = __DIR__ . '/debug.log';
    $msg = date('Y-m-d H:i:s') . " [$label] " . print_r($data, true) . "\n";
    file_put_contents($log, $msg, FILE_APPEND);
}

function dd($data)
{
    echo '<pre style="background:#111;color:#0f0;padding:15px;font-size:13px">';
    var_dump($data);
    echo '</pre>';
    wp_die();
}