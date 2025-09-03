<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once('../../config.php');
require_once($CFG->libdir.'/grouplib.php');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Set JSON response header
header('Content-Type: application/json');

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Extract parameters
    $sesskey = $input['sesskey'] ?? '';
    $courseid = $input['courseid'] ?? 0;
    $groupid = $input['groupid'] ?? 0;
    $userid = $input['userid'] ?? 0;
    $imageData = $input['imageData'] ?? '';
    
    // Validate sesskey
    if (!confirm_sesskey($sesskey)) {
        throw new Exception('Invalid session key');
    }
    
    // Validate parameters
    if (!$courseid || !$groupid || !$userid || !$imageData) {
        throw new Exception('Missing required parameters');
    }
    
    // Get course and check it exists
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    
    // Require login and check capabilities
    require_login($course);
    require_capability('local/sendafirmas:manage', context_course::instance($course->id));
    
    // Validate that group belongs to course
    $group = $DB->get_record('groups', array('id' => $groupid, 'courseid' => $courseid));
    if (!$group) {
        throw new Exception('Invalid group for this course');
    }
    
    // Validate that user is member of the group
    if (!groups_is_member($groupid, $userid)) {
        throw new Exception('User is not a member of this group');
    }
    
    if (!preg_match('/^data:image\/jpeg;base64,/', $imageData, $matches)) {
        throw new Exception('Invalid image data format - only JPEG is supported');
    }
    
    $base64Data = preg_replace('/^data:image\/jpeg;base64,/', '', $imageData);
    $imageContent = base64_decode($base64Data);
    
    if (!$imageContent) {
        throw new Exception('Failed to decode image data');
    }
    
    // Just validate it's a valid JPEG and save it as-is
    $imageInfo = getimagesizefromstring($imageContent);
    if (!$imageInfo || $imageInfo['mime'] !== 'image/jpeg') {
        throw new Exception('Invalid JPEG image');
    }

    // Get user context
    $usercontext = context_user::instance($userid);
    
    // Prepare file record
    $filerecord = array(
        'contextid' => $usercontext->id,
        'component' => 'local_sendafirmas',
        'filearea' => 'signature',
        'itemid' => $userid,
        'filepath' => '/',
        'filename' => 'firma_' . $userid . '.jpg',
        'userid' => $USER->id
    );
    
    // Get file storage
    $fs = get_file_storage();
    
    // Delete any existing signature files for this user
    $fs->delete_area_files($usercontext->id, 'local_sendafirmas', 'signature', $userid);
    
    // Create new file
    $file = $fs->create_file_from_string($filerecord, $imageContent);
    
    if (!$file) {
        throw new Exception('Failed to save signature file');
    }
    
    $public_url = new moodle_url('/local/sendafirmas/image.php', array(
        'u' => $userid
    ));
    
    // Update user profile field with simple URL
    $profilefield = $DB->get_record_sql(
        "SELECT uif.id as fieldid, uid.id as dataid
         FROM {user_info_field} uif 
         LEFT JOIN {user_info_data} uid ON uid.fieldid = uif.id AND uid.userid = ?
         WHERE uif.shortname = 'firma'",
        array($userid)
    );
    
    if (!$profilefield) {
        throw new Exception('Profile field "firma" not found');
    }
    
    if ($profilefield->dataid) {
        // Update existing record
        $DB->update_record('user_info_data', array(
            'id' => $profilefield->dataid,
            'data' => $public_url->out(false)
        ));
    } else {
        // Create new record
        $DB->insert_record('user_info_data', array(
            'userid' => $userid,
            'fieldid' => $profilefield->fieldid,
            'data' => $public_url->out(false)
        ));
    }
    
    // Return success response
    echo json_encode(array('success' => true));
    
} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>
