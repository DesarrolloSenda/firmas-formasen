<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Serves files from the local_sendafirmas file areas
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function local_sendafirmas_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $DB, $USER;

    // Allow any context level for public access

    // Only serve signature files
    if ($filearea !== 'signature') {
        return false;
    }

    // Make sure the filearea is one of those used by the plugin
    if ($filearea !== 'signature') {
        return false;
    }

    // The args is an array containing [itemid, path]
    $itemid = array_shift($args);
    $filename = array_pop($args);

    // No authorization required - public access for debugging

    // Retrieve the file from the Files API
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_sendafirmas', $filearea, $itemid, '/', $filename);
    if (!$file) {
        return false; // The file does not exist
    }

    $options['cacheability'] = 'public';
    $options['immutable'] = false;
    $options['lifetime'] = 86400; // 24 hours cache
    
    // Set proper MIME type for images
    $mimetype = $file->get_mimetype();
    if (empty($mimetype) || $mimetype === 'document/unknown') {
        // Detect MIME type from file extension
        $pathinfo = pathinfo($filename);
        $extension = strtolower($pathinfo['extension'] ?? '');
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $mimetype = 'image/jpeg';
                break;
            case 'png':
                $mimetype = 'image/png';
                break;
            case 'gif':
                $mimetype = 'image/gif';
                break;
            default:
                $mimetype = 'application/octet-stream';
        }
    }

    // Send the file back to the browser with proper headers for public access
    send_stored_file($file, 86400, 0, false, array_merge($options, array(
        'mimetype' => $mimetype,
        'dontdie' => false,
        'addlastmodified' => true
    )));
}
