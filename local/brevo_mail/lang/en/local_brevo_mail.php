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

$string['pluginname'] = 'Brevo mail';

$string['apikey'] = 'Brevo API key';
$string['apikey_desc'] = 'Paste your Brevo API key (xkeysib-...)';
$string['useforcore'] = 'Use Brevo for Moodle core email';
$string['useforcore_desc'] = 'When enabled, Moodle email_to_user() attempts sending through Brevo API first.';
$string['fallbacktonative'] = 'Fallback to Moodle native mail';
$string['fallbacktonative_desc'] = 'If Brevo API send fails, continue with native Moodle mailer (SMTP/PHP mail).';
$string['debuglog'] = 'Enable Brevo debug logging';
$string['debuglog_desc'] = 'Logs Brevo delivery success/failure details with Moodle debugging() for troubleshooting.';
$string['fromemail'] = 'Default sender email';
$string['fromemail_desc'] = 'Used when no sender email is passed in code.';
$string['fromname'] = 'Default sender name';
$string['fromname_desc'] = 'Used when no sender name is passed in code.';

$string['missingapikey'] = 'Brevo API key is not configured.';
$string['missingsender'] = 'Sender name or sender email is missing.';
$string['invalidrecipient'] = 'Recipient email is invalid.';
$string['emailsentsuccess'] = 'Email sent successfully.';
$string['unknownerror'] = 'Unknown Brevo API error.';
$string['brevoattachmentsunsupported'] = 'Brevo core-mail override does not currently support attachments.';
