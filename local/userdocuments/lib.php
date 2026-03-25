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

/**
 * Library functions for local_userdocuments plugin.
 *
 * Provides form integration, file handling, validation, and data persistence
 * for user education documents, identity proofs, and guardian information.
 *
 * @package    local_userdocuments
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the document file areas used by this plugin.
 *
 * Each area maps to one filemanager field on the user edit form.
 * The key is the file area name, and the value is the lang string key.
 *
 * @return array Associative array of filearea => lang string key.
 */
function local_custom_userdocs_get_file_areas() {
    return [
        'doc_10th'       => 'doc_10th',
        'doc_12th'       => 'doc_12th',
        'doc_graduation' => 'doc_graduation',
        'doc_resume'     => 'doc_resume',
        'doc_aadhaar'    => 'doc_aadhaar',
        'doc_pan'        => 'doc_pan',
    ];
}

/**
 * Backwards-compatible wrapper for older integrations.
 *
 * @return array
 */
function local_userdocuments_get_file_areas() {
    return local_custom_userdocs_get_file_areas();
}

/**
 * Returns the common filemanager options for all document upload fields.
 *
 * - Max file size: 10 MB.
 * - Allowed types: PDF, JPG, PNG.
 * - Multiple files allowed.
 * - No subdirectories.
 *
 * @return array Filemanager options array.
 */
function local_custom_userdocs_filemanager_options() {
    global $CFG;

    $maxbytes = 10 * 1024 * 1024; // 10 MB.
    if (!empty($CFG->maxbytes)) {
        $maxbytes = min($maxbytes, $CFG->maxbytes);
    }

    return [
        'maxbytes'       => $maxbytes,
        'maxfiles'       => 10,
        'subdirs'        => 0,
        'accepted_types' => ['.pdf', '.jpg', '.jpeg', '.png'],
    ];
}

/**
 * Backwards-compatible wrapper for older integrations.
 *
 * @return array
 */
function local_userdocuments_filemanager_options() {
    return local_custom_userdocs_filemanager_options();
}

/**
 * Determine whether the current editor should be treated as admin-level.
 *
 * @param context|null $coursecontext
 * @return bool
 */
function local_custom_userdocs_is_admin_editor(context $coursecontext = null): bool {
    global $USER;

    if (is_siteadmin($USER)) {
        return true;
    }

    if (has_capability('moodle/user:update', context_system::instance())) {
        return true;
    }

    $roleshortnames = ['manager', 'teacher', 'editingteacher'];
    $contexts = [context_system::instance()];
    if ($coursecontext) {
        $contexts[] = $coursecontext;
    }

    foreach ($contexts as $context) {
        $roles = get_user_roles($context, $USER->id, false);
        foreach ($roles as $role) {
            if (in_array($role->shortname, $roleshortnames, true)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Add custom form fields to the user edit forms.
 *
 * Adds three sections:
 *   1. Education - 4 filemanager fields (10th, 12th, Graduation, Resume).
 *   2. Personal Identity Proof - 2 filemanager fields (Aadhaar, PAN).
 *   3. Guardians - 2 text fields (name, contact number).
 *
 * @param MoodleQuickForm $mform The form object.
 * @param int $userid The user ID (-1 for new user).
 * @param bool $isadmin Whether fields are optional (true) or required (false).
 */
function local_custom_userdocs_add_fields(MoodleQuickForm $mform, int $userid, bool $isadmin) {
    $fmoptions = local_custom_userdocs_filemanager_options();

    // ---------------------------------------------------------------
    // Section 1: Education.
    // ---------------------------------------------------------------
    $mform->addElement('header', 'userdoc_education',
        get_string('header_education', 'local_userdocuments'));

    $mform->addElement('filemanager', 'userdoc_10th',
        get_string('doc_10th', 'local_userdocuments'), null, $fmoptions);
    $mform->addHelpButton('userdoc_10th', 'doc_10th', 'local_userdocuments');
    if (!$isadmin) {
        $mform->addRule('userdoc_10th',
            get_string('required_document', 'local_userdocuments'), 'required', null, 'client');
    }

    $mform->addElement('filemanager', 'userdoc_12th',
        get_string('doc_12th', 'local_userdocuments'), null, $fmoptions);
    $mform->addHelpButton('userdoc_12th', 'doc_12th', 'local_userdocuments');
    if (!$isadmin) {
        $mform->addRule('userdoc_12th',
            get_string('required_document', 'local_userdocuments'), 'required', null, 'client');
    }

    $mform->addElement('filemanager', 'userdoc_graduation',
        get_string('doc_graduation', 'local_userdocuments'), null, $fmoptions);
    $mform->addHelpButton('userdoc_graduation', 'doc_graduation', 'local_userdocuments');
    if (!$isadmin) {
        $mform->addRule('userdoc_graduation',
            get_string('required_document', 'local_userdocuments'), 'required', null, 'client');
    }

    $mform->addElement('filemanager', 'userdoc_resume',
        get_string('doc_resume', 'local_userdocuments'), null, $fmoptions);
    $mform->addHelpButton('userdoc_resume', 'doc_resume', 'local_userdocuments');
    if (!$isadmin) {
        $mform->addRule('userdoc_resume',
            get_string('required_document', 'local_userdocuments'), 'required', null, 'client');
    }

    // ---------------------------------------------------------------
    // Section 2: Personal Identity Proof.
    // ---------------------------------------------------------------
    $mform->addElement('header', 'userdoc_identity',
        get_string('header_identity', 'local_userdocuments'));

    $mform->addElement('filemanager', 'userdoc_aadhaar',
        get_string('doc_aadhaar', 'local_userdocuments'), null, $fmoptions);
    $mform->addHelpButton('userdoc_aadhaar', 'doc_aadhaar', 'local_userdocuments');
    if (!$isadmin) {
        $mform->addRule('userdoc_aadhaar',
            get_string('required_document', 'local_userdocuments'), 'required', null, 'client');
    }

    $mform->addElement('filemanager', 'userdoc_pan',
        get_string('doc_pan', 'local_userdocuments'), null, $fmoptions);
    $mform->addHelpButton('userdoc_pan', 'doc_pan', 'local_userdocuments');
    if (!$isadmin) {
        $mform->addRule('userdoc_pan',
            get_string('required_document', 'local_userdocuments'), 'required', null, 'client');
    }

    // ---------------------------------------------------------------
    // Section 3: Guardians.
    // ---------------------------------------------------------------
    $mform->addElement('header', 'userdoc_guardians',
        get_string('header_guardians', 'local_userdocuments'));

    $mform->addElement('text', 'userdoc_guardian_name',
        get_string('guardian_name', 'local_userdocuments'), 'maxlength="255" size="30"');
    $mform->setType('userdoc_guardian_name', PARAM_TEXT);
    if (!$isadmin) {
        $mform->addRule('userdoc_guardian_name',
            get_string('required_guardian_name', 'local_userdocuments'), 'required', null, 'client');
    }

    $mform->addElement('text', 'userdoc_guardian_contact',
        get_string('guardian_contact', 'local_userdocuments'), 'maxlength="10" size="15"');
    $mform->setType('userdoc_guardian_contact', PARAM_RAW);
    if (!$isadmin) {
        $mform->addRule('userdoc_guardian_contact',
            get_string('required_guardian_contact', 'local_userdocuments'), 'required', null, 'client');
    }

    $mform->addElement('text', 'userdoc_guardian_relationship',
        get_string('guardian_relationship', 'local_userdocuments'), 'maxlength="100" size="30"');
    $mform->setType('userdoc_guardian_relationship', PARAM_TEXT);
    if (!$isadmin) {
        $mform->addRule('userdoc_guardian_relationship',
            get_string('required_guardian_relationship', 'local_userdocuments'), 'required', null, 'client');
    }
}

/**
 * Backwards-compatible wrapper for older integrations.
 *
 * @param MoodleQuickForm $mform
 * @param int $userid
 */
function local_userdocuments_add_form_fields(&$mform, $userid) {
    local_custom_userdocs_add_fields($mform, $userid, false);
}

/**
 * Validate the custom user document and guardian form fields.
 *
 * Checks:
 *   - Each filemanager draft area contains at least one file.
 *   - Guardian name is not empty (when required).
 *   - Guardian contact is exactly 10 digits (numeric only).
 *
 * @param stdClass $data The submitted form data (as object).
 * @param array $files The submitted files array.
 * @param bool $isadmin Whether fields are optional (true) or required (false).
 * @return array Associative array of field => error message. Empty if valid.
 */
function local_custom_userdocs_validation($data, $files, bool $isadmin) {
    $errors = [];

    // Validate each filemanager has at least one real file in its draft area.
    if (!$isadmin) {
        $fileareas = local_custom_userdocs_get_file_areas();
        foreach ($fileareas as $filearea => $langkey) {
            // Map file area name to form field name: doc_10th => userdoc_10th, etc.
            $shortname = substr($filearea, 4); // Remove 'doc_' prefix.
            $fieldname = 'userdoc_' . $shortname;

            $draftitemid = $data->$fieldname ?? 0;
            if (empty($draftitemid)) {
                $errors[$fieldname] = get_string('required_document', 'local_userdocuments');
                continue;
            }

            $draftinfo = file_get_draft_area_info($draftitemid);
            if (empty($draftinfo['filecount'])) {
                $errors[$fieldname] = get_string('required_document', 'local_userdocuments');
            }
        }
    }

    // Validate guardian name.
    $guardianname = trim($data->userdoc_guardian_name ?? '');
    if (!$isadmin && $guardianname === '') {
        $errors['userdoc_guardian_name'] = get_string('required_guardian_name', 'local_userdocuments');
    }

    // Validate guardian contact number.
    $contact = trim($data->userdoc_guardian_contact ?? '');
    if (!$isadmin && $contact === '') {
        $errors['userdoc_guardian_contact'] = get_string('required_guardian_contact', 'local_userdocuments');
    } else if ($contact !== '') {
        if (!ctype_digit($contact)) {
            $errors['userdoc_guardian_contact'] = get_string('error_contact_digits', 'local_userdocuments');
        } else if (strlen($contact) !== 10) {
            $errors['userdoc_guardian_contact'] = get_string('error_contact_length', 'local_userdocuments');
        }
    }

    // Validate guardian relationship.
    $relationship = trim($data->userdoc_guardian_relationship ?? '');
    if (!$isadmin && $relationship === '') {
        $errors['userdoc_guardian_relationship'] = get_string('required_guardian_relationship', 'local_userdocuments');
    }

    return $errors;
}

/**
 * Backwards-compatible wrapper for older integrations.
 *
 * @param stdClass $data
 * @param array $files
 * @return array
 */
function local_userdocuments_validation($data, $files) {
    return local_custom_userdocs_validation($data, $files, false);
}

/**
 * Prepare file draft areas and load guardian data for the user edit form.
 *
 * For each document file area, calls file_prepare_draft_area()
 * so that existing files appear in the filemanager when editing a user.
 * Also loads guardian name and contact from the database.
 *
 * @param stdClass $user The user object, modified by reference.
 */
function local_custom_userdocs_prepare_draft_areas(&$user) {
    global $DB;

    // Only prepare draft areas for existing users.
    if ($user->id <= 0) {
        return;
    }

    $usercontext = context_user::instance($user->id);
    $fmoptions = local_custom_userdocs_filemanager_options();
    $fileareas = local_custom_userdocs_get_file_areas();

    // Prepare each file area's draft area so existing files show up in the form.
    foreach ($fileareas as $filearea => $langkey) {
        $shortname = substr($filearea, 4); // Remove 'doc_' prefix.
        $fieldname = 'userdoc_' . $shortname;

        $draftitemid = 0; // Will be populated by file_prepare_draft_area().
        file_prepare_draft_area(
            $draftitemid,
            $usercontext->id,
            'local_userdocuments',
            $filearea,
            $user->id,
            $fmoptions
        );
        $user->$fieldname = $draftitemid;
    }

    // Load guardian data from the database.
    $guardian = $DB->get_record('local_userdocuments_guardian', ['userid' => $user->id]);
    if ($guardian) {
        $user->userdoc_guardian_name    = $guardian->guardian_name;
        $user->userdoc_guardian_contact = $guardian->guardian_contact;
        $user->userdoc_guardian_relationship = $guardian->guardian_relationship ?? '';
    }
}

/**
 * Backwards-compatible wrapper for older integrations.
 *
 * @param stdClass $user
 */
function local_userdocuments_prepare_draft_areas(&$user) {
    local_custom_userdocs_prepare_draft_areas($user);
}

/**
 * Save uploaded documents and guardian data after form submission.
 *
 * For each document file area, calls file_save_draft_area_files()
 * to persist files from the draft area to the permanent storage.
 * Also inserts or updates the guardian record in the database.
 *
 * @param stdClass $usernew The submitted user data object.
 */
function local_custom_userdocs_save_data($usernew) {
    global $DB;

    // We need a valid user ID to save files against.
    if (empty($usernew->id) || $usernew->id <= 0) {
        return;
    }

    $usercontext = context_user::instance($usernew->id);
    $fmoptions = local_custom_userdocs_filemanager_options();
    $fileareas = local_custom_userdocs_get_file_areas();

    // Save each file area from the draft area to permanent storage.
    foreach ($fileareas as $filearea => $langkey) {
        $shortname = substr($filearea, 4); // Remove 'doc_' prefix.
        $fieldname = 'userdoc_' . $shortname;

        if (isset($usernew->$fieldname)) {
            file_save_draft_area_files(
                $usernew->$fieldname,   // Draft item ID from the form.
                $usercontext->id,       // Context ID (user context).
                'local_userdocuments',  // Component.
                $filearea,              // File area.
                $usernew->id,           // Item ID (user id).
                $fmoptions              // Options.
            );
        }
    }

    // Save alternate email as user preference
    if (isset($usernew->alternateemail)) {
        set_user_preference('alternateemail', $usernew->alternateemail, $usernew->id);
    }

    // Save guardian data (insert or update).
    $guardianname    = trim($usernew->userdoc_guardian_name ?? '');
    $guardiancontact = trim($usernew->userdoc_guardian_contact ?? '');
    $guardianrel     = trim($usernew->userdoc_guardian_relationship ?? '');

    $existing = $DB->get_record('local_userdocuments_guardian', ['userid' => $usernew->id]);
    if ($guardianname === '' && $guardiancontact === '' && $guardianrel === '') {
        if ($existing) {
            $DB->delete_records('local_userdocuments_guardian', ['id' => $existing->id]);
        }
        return;
    }

    if ($existing) {
        // Update existing record.
        $existing->guardian_name    = $guardianname;
        $existing->guardian_contact = $guardiancontact;
        $existing->guardian_relationship = $guardianrel;
        $existing->timemodified     = time();
        $DB->update_record('local_userdocuments_guardian', $existing);
    } else {
        // Insert new record.
        $record = new stdClass();
        $record->userid           = $usernew->id;
        $record->guardian_name    = $guardianname;
        $record->guardian_contact = $guardiancontact;
        $record->guardian_relationship = $guardianrel;
        $record->timecreated      = time();
        $record->timemodified     = time();
        $DB->insert_record('local_userdocuments_guardian', $record);
    }
}

/**
 * Backwards-compatible wrapper for older integrations.
 *
 * @param stdClass $usernew
 */
function local_userdocuments_save_data($usernew) {
    local_custom_userdocs_save_data($usernew);
}

/**
 * Serve files for the local_userdocuments plugin.
 *
 * This callback is required by Moodle's File API to serve stored files
 * when they are accessed via pluginfile.php URLs.
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object (unused).
 * @param context $context The file context.
 * @param string $filearea The file area name.
 * @param array $args Extra arguments (itemid, filepath, filename).
 * @param bool $forcedownload Whether to force download.
 * @param array $options Additional options.
 * @return bool false if the file is not found.
 */
function local_userdocuments_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $USER;

    // Only serve files from user contexts.
    if ($context->contextlevel !== CONTEXT_USER) {
        return false;
    }

    // Require login.
    require_login();

    // Verify the file area is one of ours.
    $validareas = local_custom_userdocs_get_file_areas();
    if (!array_key_exists($filearea, $validareas)) {
        return false;
    }

    // Check the user has permission to view this user's files.
    $userid = $context->instanceid;
    if ($USER->id != $userid) {
        $systemcontext = context_system::instance();
        require_capability('moodle/user:update', $systemcontext);
    }

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_userdocuments', $filearea, $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
    return true;
}
