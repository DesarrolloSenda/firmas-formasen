<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

ob_start();
error_reporting(0); // Suppress all PHP errors/notices for clean image output

require_once('../../config.php');

ob_clean();

$userid = optional_param('u', 0, PARAM_INT);

if (empty($userid)) {
    http_response_code(400);
    header('Content-Type: text/plain');
    die('Bad request');
}

// Get user context with better error handling
try {
    $usercontext = context_user::instance($userid);
    if (!$usercontext) {
        throw new Exception('Invalid user context');
    }
} catch (Exception $e) {
    http_response_code(404);
    header('Content-Type: text/plain');
    die('User not found');
}

// Get file from File API
$fs = get_file_storage();
$file = $fs->get_file(
    $usercontext->id,
    'local_sendafirmas',
    'signature',
    $userid,
    '/',
    'firma_' . $userid . '.jpg'
);

if (!$file || $file->is_directory()) {
    http_response_code(404);
    header('Content-Type: text/plain');
    die('Signature not found');
}

header('Content-Type: image/jpeg');
header('Content-Length: ' . $file->get_filesize());
header('Cache-Control: public, max-age=86400');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $file->get_timemodified()) . ' GMT');
header('ETag: "' . md5($file->get_contenthash()) . '"');
header('Accept-Ranges: bytes');
header('Access-Control-Allow-Origin: *');
header('Content-Disposition: inline; filename="signature_' . $userid . '.jpg"');

ob_end_clean();

// Output file content
$file->readfile();
exit;
?>
