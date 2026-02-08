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

namespace local_customservice\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/user/lib.php');

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External function to create a new user in Moodle.
 *
 * @package    local_customservice
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_user extends external_api
{

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters
    {
        return new external_function_parameters([
            'firstname' => new external_value(PARAM_TEXT, 'First name of the user'),
            'lastname' => new external_value(PARAM_TEXT, 'Last name of the user'),
            'email' => new external_value(PARAM_EMAIL, 'Email address of the user (also used as username)'),
            'phone1' => new external_value(PARAM_TEXT, 'Contact number of the user', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Create a new user in Moodle.
     *
     * Username is automatically set to the email address.
     * Password is auto-generated and sent via welcome email.
     *
     * @param string $firstname First name of the user.
     * @param string $lastname Last name of the user.
     * @param string $email Email address of the user (also used as username).
     * @param string $phone1 Contact number of the user.
     * @return array Result with success status, user id, username, and message.
     */
    public static function execute(
        string $firstname,
        string $lastname,
        string $email,
        string $phone1 = ''
        ): array
    {
        global $CFG, $DB;

        // Validate context and capability.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('moodle/user:create', $context);

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'phone1' => $phone1,
        ]);

        // Extract validated parameters.
        $firstname = trim($params['firstname']);
        $lastname = trim($params['lastname']);
        $email = trim($params['email']);
        $phone1 = trim($params['phone1']);

        // Validate mandatory fields.
        if (empty($firstname)) {
            return self::error_response('First name is required.');
        }
        if (empty($lastname)) {
            return self::error_response('Last name is required.');
        }
        if (empty($email)) {
            return self::error_response('Email address is required.');
        }

        // Validate email format.
        if (!validate_email($email)) {
            return self::error_response('Invalid email address format: ' . $email);
        }

        // Check for duplicate email (if not allowed).
        if (empty($CFG->allowaccountssameemail)) {
            $select = $DB->sql_equal('email', ':email', false) . ' AND mnethostid = :mnethostid AND deleted = 0';
            $sqlparams = [
                'email' => $email,
                'mnethostid' => $CFG->mnet_localhost_id,
            ];
            if ($DB->record_exists_select('user', $select, $sqlparams)) {
                return self::error_response('Email address already exists: ' . $email);
            }
        }

        // Use email as username (lowercased).
        $username = \core_text::strtolower($email);

        // Clean the username.
        $username = \core_user::clean_field($username, 'username');

        // Check if username already exists.
        if ($DB->record_exists('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id])) {
            return self::error_response('A user with this email already exists: ' . $email);
        }

        // Auto-generate a secure password.
        $plainpassword = generate_password();

        // Prepare user data.
        $user = new \stdClass();
        $user->username = $username;
        $user->firstname = $firstname;
        $user->lastname = $lastname;
        $user->email = $email;
        $user->phone1 = $phone1;
        $user->auth = 'manual';
        $user->confirmed = 1;
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->password = $plainpassword;

        try {
            // Create the user using Moodle core API (with password hashing).
            $userid = user_create_user($user, true, false);

            if ($userid) {
                // Send welcome email with username and password.
                $createduser = $DB->get_record('user', ['id' => $userid]);
                self::send_welcome_email($createduser, $plainpassword);
            }

            return [
                'success' => true,
                'userid' => $userid,
                'username' => $username,
                'message' => 'User created successfully. Welcome email with login credentials has been sent.',
            ];

        }
        catch (\Exception $e) {
            return self::error_response('Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Send welcome email with username and password to the newly created user.
     *
     * @param \stdClass $user The user object.
     * @param string $plainpassword The plain text password.
     * @return bool True if email was sent successfully.
     */
    private static function send_welcome_email(\stdClass $user, string $plainpassword): bool
    {
        global $CFG, $SITE;

        $supportuser = \core_user::get_support_user();

        $subject = get_string('newusernewpasswordsubj', '', format_string($SITE->fullname));

        $a = new \stdClass();
        $a->firstname = $user->firstname;
        $a->lastname = $user->lastname;
        $a->sitename = format_string($SITE->fullname);
        $a->username = $user->username;
        $a->password = $plainpassword;
        $a->link = $CFG->wwwroot . '/login/';

        $messagetext = "Hello {$a->firstname} {$a->lastname},\n\n";
        $messagetext .= "Welcome to {$a->sitename}!\n\n";
        $messagetext .= "Your account has been created with the following login credentials:\n\n";
        $messagetext .= "Username: {$a->username}\n";
        $messagetext .= "Password: {$a->password}\n\n";
        $messagetext .= "You can login at: {$a->link}\n\n";
        $messagetext .= "For security reasons, we recommend that you change your password after your first login.\n\n";
        $messagetext .= "Best regards,\n";
        $messagetext .= "{$a->sitename} Team";

        $messagehtml = "<p>Hello {$a->firstname} {$a->lastname},</p>";
        $messagehtml .= "<p>Welcome to <strong>{$a->sitename}</strong>!</p>";
        $messagehtml .= "<p>Your account has been created with the following login credentials:</p>";
        $messagehtml .= "<p><strong>Username:</strong> {$a->username}<br>";
        $messagehtml .= "<strong>Password:</strong> {$a->password}</p>";
        $messagehtml .= "<p>You can login at: <a href=\"{$a->link}\">{$a->link}</a></p>";
        $messagehtml .= "<p>For security reasons, we recommend that you change your password after your first login.</p>";
        $messagehtml .= "<p>Best regards,<br>{$a->sitename} Team</p>";

        return email_to_user($user, $supportuser, $subject, $messagetext, $messagehtml);
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure
    {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'True if user was created successfully'),
            'userid' => new external_value(PARAM_INT, 'ID of the created user (0 on failure)'),
            'username' => new external_value(PARAM_TEXT, 'Username of the created user'),
            'message' => new external_value(PARAM_TEXT, 'Success or error message'),
        ]);
    }


    /**
     * Return an error response structure.
     *
     * @param string $message Error message.
     * @return array Error response.
     */
    private static function error_response(string $message): array
    {
        return [
            'success' => false,
            'userid' => 0,
            'username' => '',
            'message' => $message,
        ];
    }
}
