<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('block_continue_learning', get_string('pluginname', 'block_continue_learning'));

    $settings->add(new admin_setting_configtext(
        'block_continue_learning/limit',
        get_string('limit', 'block_continue_learning'),
        get_string('limit_desc', 'block_continue_learning'),
        6,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_continue_learning/showprogress',
        get_string('showprogress', 'block_continue_learning'),
        get_string('showprogress_desc', 'block_continue_learning'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_continue_learning/showimages',
        get_string('showimages', 'block_continue_learning'),
        get_string('showimages_desc', 'block_continue_learning'),
        1
    ));

    $ADMIN->add('blocks', $settings);
}
