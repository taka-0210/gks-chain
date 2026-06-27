<?php
header('Content-Type: text/plain; charset=UTF-8');

echo 'PHP_VERSION: ' . phpversion() . "\n";
echo 'SERVER_SOFTWARE: ' . (isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '') . "\n";
echo 'DOCUMENT_ROOT: ' . (isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '') . "\n";
echo 'SCRIPT_FILENAME: ' . __FILE__ . "\n";
echo 'json: ' . (extension_loaded('json') ? 'yes' : 'no') . "\n";
echo 'mbstring: ' . (extension_loaded('mbstring') ? 'yes' : 'no') . "\n";
echo 'fileinfo: ' . (extension_loaded('fileinfo') ? 'yes' : 'no') . "\n";
echo 'gd: ' . (extension_loaded('gd') ? 'yes' : 'no') . "\n";
