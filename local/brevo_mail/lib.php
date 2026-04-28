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

/**
 * Helper to send one email through Brevo.
 *
 * @param string $toemail
 * @param string $toname
 * @param string $subject
 * @param string $htmlcontent
 * @param string|null $fromemail
 * @param string|null $fromname
 * @param string|null $apikey
 * @return array
 */
function local_brevo_mail_send_email(
    string $toemail,
    string $toname,
    string $subject,
    string $htmlcontent,
    ?string $fromemail = null,
    ?string $fromname = null,
    ?string $apikey = null
): array {
    $mailer = new \local_brevo_mail\mailer($apikey, $fromemail, $fromname);
    return $mailer->send_email($toemail, $toname, $subject, $htmlcontent, $fromemail, $fromname);
}

/**
 * Helper to send same email to many recipients.
 *
 * @param array $recipients
 * @param string $subject
 * @param string $htmlcontent
 * @param string|null $fromemail
 * @param string|null $fromname
 * @param string|null $apikey
 * @return array
 */
function local_brevo_mail_send_bulk(
    array $recipients,
    string $subject,
    string $htmlcontent,
    ?string $fromemail = null,
    ?string $fromname = null,
    ?string $apikey = null
): array {
    $mailer = new \local_brevo_mail\mailer($apikey, $fromemail, $fromname);
    return $mailer->send_bulk($recipients, $subject, $htmlcontent, $fromemail, $fromname);
}

/**
 * Send Moodle core email payload via Brevo API.
 *
 * @param stdClass $user
 * @param mixed $from
 * @param string $subject
 * @param string $messagetext
 * @param string $messagehtml
 * @param bool $usetrueaddress
 * @param string $replyto
 * @param string $replytoname
 * @return array
 */
function local_brevo_mail_send_moodle_email(
    stdClass $user,
    $from,
    string $subject,
    string $messagetext,
    string $messagehtml = '',
    bool $usetrueaddress = true,
    string $replyto = '',
    string $replytoname = ''
): array {
    global $CFG, $SITE;

    $defaultfromemail = (string)get_config('local_brevo_mail', 'fromemail');
    if (empty($defaultfromemail)) {
        $fallback = empty($CFG->noreplyaddress) ? ('noreply@' . get_host_from_url($CFG->wwwroot)) : $CFG->noreplyaddress;
        $defaultfromemail = (string)$fallback;
    }

    $defaultfromname = (string)get_config('local_brevo_mail', 'fromname');
    if (empty($defaultfromname)) {
        $defaultfromname = format_string($SITE->shortname);
    }

    $senderemail = $defaultfromemail;
    $sendername = $defaultfromname;

    if (is_string($from)) {
        $sendername = $from;
    } else if (is_object($from) && $usetrueaddress && can_send_from_real_email_address($from, $user) && validate_email($from->email)) {
        $senderemail = $from->email;
        $sendername = fullname($from);
    } else if (is_object($from)) {
        $sendername = fullname($from);
    }

    if (!validate_email($senderemail)) {
        return [
            'success' => false,
            'error' => 'Invalid sender email: ' . $senderemail,
        ];
    }

    if (empty($messagehtml)) {
        $messagehtml = text_to_html($messagetext);
    }

    if (empty($replyto) || !validate_email($replyto)) {
        $replyto = '';
        $replytoname = '';
    }

    $recipientname = trim((string)fullname($user));
    if ($recipientname === '') {
        $recipientname = (string)$user->email;
    }

    $mailer = new \local_brevo_mail\mailer();
    return $mailer->send_email_advanced(
        $user->email,
        $recipientname,
        $subject,
        $messagehtml,
        $messagetext,
        $senderemail,
        $sendername,
        $replyto,
        $replytoname
    );
}
