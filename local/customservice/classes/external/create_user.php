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
            'email' => new external_value(PARAM_EMAIL, 'Email address of the user'),
            'phone1' => new external_value(PARAM_TEXT, 'Contact number of the user', VALUE_DEFAULT, ''),
            'username' => new external_value(PARAM_USERNAME, 'Username (auto-generated if not provided)', VALUE_DEFAULT, ''),
            'password' => new external_value(PARAM_RAW, 'Password (generated if createpassword is true)', VALUE_DEFAULT, ''),
            'createpassword' => new external_value(PARAM_BOOL, 'If true, generate and email password to user', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Create a new user in Moodle.
     *
     * @param string $firstname First name of the user.
     * @param string $lastname Last name of the user.
     * @param string $email Email address of the user.
     * @param string $phone1 Contact number of the user.
     * @param string $username Username (optional, auto-generated if empty).
     * @param string $password Password (optional).
     * @param bool $createpassword If true, generate and email password to user.
     * @return array Result with success status, user id, username, and message.
     */
    public static function execute(
        string $firstname,
        string $lastname,
        string $email,
        string $phone1 = '',
        string $username = '',
        string $password = '',
        bool $createpassword = true
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
            'username' => $username,
            'password' => $password,
            'createpassword' => $createpassword,
        ]);

        // Extract validated parameters.
        $firstname = trim($params['firstname']);
        $lastname = trim($params['lastname']);
        $email = trim($params['email']);
        $phone1 = trim($params['phone1']);
        $username = trim($params['username']);
        $password = $params['password'];
        $createpassword = $params['createpassword'];

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

        // Generate username if not provided.
        if (empty($username)) {
            $username = self::generate_username($email);
        }

        // Ensure username is lowercase.
        $username = \core_text::strtolower($username);

        // Clean the username.
        $username = \core_user::clean_field($username, 'username');

        // Check if username already exists, add numeric suffix if needed.
        $baseusername = $username;
        $counter = 1;
        while ($DB->record_exists('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id])) {
            $username = $baseusername . $counter;
            $counter++;
            if ($counter > 100) {
                return self::error_response('Unable to generate a unique username.');
            }
        }

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

        // Handle password.
        $updatepassword = false;
        if ($createpassword) {
            // Password will be generated and emailed.
            $user->password = '';
        }
        else if (!empty($password)) {
            // Use provided password.
            $user->password = $password;
            $updatepassword = true;
        }
        else {
            // No password provided and not creating one - error.
            return self::error_response('You must provide a password or set createpassword to true.');
        }

        try {
            // Create the user using Moodle core API.
            $userid = user_create_user($user, $updatepassword, true);

            // Send password creation email if requested.
            if ($createpassword && $userid) {
                setnew_password_and_mail($DB->get_record('user', ['id' => $userid]));
            }

            return [
                'success' => true,
                'userid' => $userid,
                'username' => $username,
                'message' => 'User created successfully.',
            ];

        }
        catch (\Exception $e) {
            return self::error_response('Failed to create user: ' . $e->getMessage());
        }
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
     * Generate a username from email address.
     *
     * @param string $email Email address.
     * @return string Generated username.
     */
    private static function generate_username(string $email): string
    {
        // Get the part before @ symbol.
        $parts = explode('@', $email);
        $username = $parts[0];

        // Remove any non-alphanumeric characters except dots, underscores, and hyphens.
        $username = preg_replace('/[^a-zA-Z0-9._-]/', '', $username);

        // Ensure it's not empty.
        if (empty($username)) {
            $username = 'user' . time();
        }

        return $username;
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
