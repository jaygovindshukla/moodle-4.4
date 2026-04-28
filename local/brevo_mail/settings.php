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
    $settings = new admin_settingpage(
        'local_brevo_mail',
        get_string('pluginname', 'local_brevo_mail')
    );

    $settings->add(new admin_setting_configpasswordunmask(
        'local_brevo_mail/apikey',
        get_string('apikey', 'local_brevo_mail'),
        get_string('apikey_desc', 'local_brevo_mail'),
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_brevo_mail/useforcore',
        get_string('useforcore', 'local_brevo_mail'),
        get_string('useforcore_desc', 'local_brevo_mail'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_brevo_mail/fallbacktonative',
        get_string('fallbacktonative', 'local_brevo_mail'),
        get_string('fallbacktonative_desc', 'local_brevo_mail'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_brevo_mail/debuglog',
        get_string('debuglog', 'local_brevo_mail'),
        get_string('debuglog_desc', 'local_brevo_mail'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'local_brevo_mail/fromemail',
        get_string('fromemail', 'local_brevo_mail'),
        get_string('fromemail_desc', 'local_brevo_mail'),
        '',
        PARAM_EMAIL
    ));

    $settings->add(new admin_setting_configtext(
        'local_brevo_mail/fromname',
        get_string('fromname', 'local_brevo_mail'),
        get_string('fromname_desc', 'local_brevo_mail'),
        '',
        PARAM_TEXT
    ));

    $ADMIN->add('localplugins', $settings);
}
