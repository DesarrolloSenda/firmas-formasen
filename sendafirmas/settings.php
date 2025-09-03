<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_sendafirmas', get_string('pluginname', 'local_sendafirmas'));
    
    $settings->add(new admin_setting_configtext(
        'local_sendafirmas/secrethmac',
        get_string('secrethmac', 'local_sendafirmas'),
        get_string('secrethmac_desc', 'local_sendafirmas'),
        '',
        PARAM_RAW_TRIMMED
    ));
    
    $ADMIN->add('localplugins', $settings);
}
?>
