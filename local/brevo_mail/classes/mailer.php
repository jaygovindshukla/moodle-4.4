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

namespace local_brevo_mail;

defined('MOODLE_INTERNAL') || die();

/**
 * Brevo mail service.
 *
 * @package    local_brevo_mail
 */
class mailer {
    /** @var string */
    private const API_BASE_URL = 'https://api.brevo.com/v3';

    /** @var string */
    private $apikey;

    /** @var string */
    private $fromemail;

    /** @var string */
    private $fromname;

    /**
     * Constructor.
     *
     * @param string|null $apikey
     * @param string|null $fromemail
     * @param string|null $fromname
     */
    public function __construct(?string $apikey = null, ?string $fromemail = null, ?string $fromname = null) {
        $this->apikey = trim((string)($apikey ?? get_config('local_brevo_mail', 'apikey')));
        $this->fromemail = trim((string)($fromemail ?? get_config('local_brevo_mail', 'fromemail')));
        $this->fromname = trim((string)($fromname ?? get_config('local_brevo_mail', 'fromname')));
    }

    /**
     * Send a single HTML email through Brevo.
     *
     * @param string $toemail
     * @param string $toname
     * @param string $subject
     * @param string $htmlcontent
     * @param string|null $fromemail
     * @param string|null $fromname
     * @return array{success: bool, messageId?: string|null, message?: string, error?: string, code?: int, response?: mixed}
     */
    public function send_email(
        string $toemail,
        string $toname,
        string $subject,
        string $htmlcontent,
        ?string $fromemail = null,
        ?string $fromname = null
    ): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $senderemail = trim((string)($fromemail ?? $this->fromemail));
        $sendername = trim((string)($fromname ?? $this->fromname));

        if (empty($this->apikey)) {
            return [
                'success' => false,
                'error' => get_string('missingapikey', 'local_brevo_mail'),
            ];
        }

        if (empty($senderemail) || empty($sendername)) {
            return [
                'success' => false,
                'error' => get_string('missingsender', 'local_brevo_mail'),
            ];
        }

        if (!validate_email($toemail)) {
            return [
                'success' => false,
                'error' => get_string('invalidrecipient', 'local_brevo_mail'),
            ];
        }

        $recipient = ['email' => $toemail];
        if (trim($toname) !== '') {
            $recipient['name'] = $toname;
        }

        $payload = [
            'sender' => [
                'name' => $sendername,
                'email' => $senderemail,
            ],
            'to' => [$recipient],
            'subject' => $subject,
            'htmlContent' => $htmlcontent,
        ];

        $curl = new \curl();
        $headers = [
            'accept: application/json',
            'api-key: ' . $this->apikey,
            'content-type: application/json',
        ];

        $options = [
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_TIMEOUT' => 30,
            'CURLOPT_HTTPHEADER' => $headers,
        ];

        $responsebody = $curl->post(
            self::API_BASE_URL . '/smtp/email',
            json_encode($payload),
            $options
        );

        $httpcode = (int)$curl->get_info()['http_code'];
        $decoded = json_decode((string)$responsebody, true);
        $decoded = is_array($decoded) ? $decoded : [];

        if ($httpcode >= 200 && $httpcode < 300) {
            return [
                'success' => true,
                'messageId' => $decoded['messageId'] ?? null,
                'message' => get_string('emailsentsuccess', 'local_brevo_mail'),
            ];
        }

        return [
            'success' => false,
            'error' => $decoded['message'] ?? get_string('unknownerror', 'local_brevo_mail'),
            'code' => $httpcode,
            'response' => $decoded,
        ];
    }

    /**
     * Send a single email with extended payload options.
     *
     * @param string $toemail
     * @param string $toname
     * @param string $subject
     * @param string $htmlcontent
     * @param string $textcontent
     * @param string|null $fromemail
     * @param string|null $fromname
     * @param string|null $replytoemail
     * @param string|null $replytoname
     * @return array{success: bool, messageId?: string|null, message?: string, error?: string, code?: int, response?: mixed}
     */
    public function send_email_advanced(
        string $toemail,
        string $toname,
        string $subject,
        string $htmlcontent = '',
        string $textcontent = '',
        ?string $fromemail = null,
        ?string $fromname = null,
        ?string $replytoemail = null,
        ?string $replytoname = null
    ): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $senderemail = trim((string)($fromemail ?? $this->fromemail));
        $sendername = trim((string)($fromname ?? $this->fromname));

        if (empty($this->apikey)) {
            return [
                'success' => false,
                'error' => get_string('missingapikey', 'local_brevo_mail'),
            ];
        }

        if (empty($senderemail) || empty($sendername)) {
            return [
                'success' => false,
                'error' => get_string('missingsender', 'local_brevo_mail'),
            ];
        }

        if (!validate_email($toemail)) {
            return [
                'success' => false,
                'error' => get_string('invalidrecipient', 'local_brevo_mail'),
            ];
        }

        if (empty($htmlcontent) && empty($textcontent)) {
            $textcontent = get_string('unknownerror', 'local_brevo_mail');
        }

        $recipient = ['email' => $toemail];
        if (trim($toname) !== '') {
            $recipient['name'] = $toname;
        }

        $payload = [
            'sender' => [
                'name' => $sendername,
                'email' => $senderemail,
            ],
            'to' => [$recipient],
            'subject' => $subject,
        ];

        if (!empty($htmlcontent)) {
            $payload['htmlContent'] = $htmlcontent;
        }

        if (!empty($textcontent)) {
            $payload['textContent'] = $textcontent;
        }

        if (!empty($replytoemail) && validate_email($replytoemail)) {
            $payload['replyTo'] = [
                'email' => $replytoemail,
                'name' => (string)$replytoname,
            ];
        }

        $curl = new \curl();
        $headers = [
            'accept: application/json',
            'api-key: ' . $this->apikey,
            'content-type: application/json',
        ];

        $options = [
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_TIMEOUT' => 30,
            'CURLOPT_HTTPHEADER' => $headers,
        ];

        $responsebody = $curl->post(
            self::API_BASE_URL . '/smtp/email',
            json_encode($payload),
            $options
        );

        $httpcode = (int)$curl->get_info()['http_code'];
        $decoded = json_decode((string)$responsebody, true);
        $decoded = is_array($decoded) ? $decoded : [];

        if ($httpcode >= 200 && $httpcode < 300) {
            return [
                'success' => true,
                'messageId' => $decoded['messageId'] ?? null,
                'message' => get_string('emailsentsuccess', 'local_brevo_mail'),
            ];
        }

        return [
            'success' => false,
            'error' => $decoded['message'] ?? get_string('unknownerror', 'local_brevo_mail'),
            'code' => $httpcode,
            'response' => $decoded,
        ];
    }

    /**
     * Send same email to many recipients.
     *
     * @param array $recipients [['email' => 'a@x.com', 'name' => 'Name'], ...]
     * @param string $subject
     * @param string $htmlcontent
     * @param string|null $fromemail
     * @param string|null $fromname
     * @return array
     */
    public function send_bulk(
        array $recipients,
        string $subject,
        string $htmlcontent,
        ?string $fromemail = null,
        ?string $fromname = null
    ): array {
        $results = [];

        foreach ($recipients as $recipient) {
            $email = (string)($recipient['email'] ?? '');
            $name = (string)($recipient['name'] ?? '');

            $results[] = [
                'recipient' => $email,
                'result' => $this->send_email(
                    $email,
                    $name,
                    $subject,
                    $htmlcontent,
                    $fromemail,
                    $fromname
                ),
            ];
        }

        return $results;
    }
}
