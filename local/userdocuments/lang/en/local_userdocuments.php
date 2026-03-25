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
 * Language strings for local_userdocuments plugin.
 *
 * @package    local_userdocuments
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin name.
$string['pluginname'] = 'User Documents & Guardian Info';

// Section headers.
$string['header_education'] = 'Education';
$string['header_identity'] = 'Personal Identity Proof';
$string['header_guardians'] = 'Guardians';

// Education document fields.
$string['doc_10th'] = '10th Documents';
$string['doc_10th_help'] = 'Upload your 10th standard certificates and marksheets. Allowed formats: PDF, JPG, PNG. Max 10MB.';
$string['doc_12th'] = '12th Documents';
$string['doc_12th_help'] = 'Upload your 12th standard certificates and marksheets. Allowed formats: PDF, JPG, PNG. Max 10MB.';
$string['doc_graduation'] = 'Graduation Documents';
$string['doc_graduation_help'] = 'Upload your graduation certificates and marksheets. Allowed formats: PDF, JPG, PNG. Max 10MB.';
$string['doc_resume'] = 'Resume';
$string['doc_resume_help'] = 'Upload your resume or CV. Allowed formats: PDF, JPG, PNG. Max 10MB.';

// Identity document fields.
$string['doc_aadhaar'] = 'Aadhaar Card Document';
$string['doc_aadhaar_help'] = 'Upload a scanned copy of your Aadhaar card. Allowed formats: PDF, JPG, PNG. Max 10MB.';
$string['doc_pan'] = 'PAN Card Document';
$string['doc_pan_help'] = 'Upload a scanned copy of your PAN card. Allowed formats: PDF, JPG, PNG. Max 10MB.';

// Guardian fields.
$string['guardian_name'] = 'Guardian Name';
$string['guardian_contact'] = 'Guardian Contact Number';
$string['guardian_relationship'] = 'Relationship With Guardian';

// Validation messages.
$string['required_document'] = 'Please upload the required document(s).';
$string['required_guardian_name'] = 'Guardian name is required.';
$string['required_guardian_contact'] = 'Guardian contact number is required.';
$string['required_guardian_relationship'] = 'Guardian relationship is required.';
$string['error_contact_digits'] = 'Guardian contact number must contain only digits.';
$string['error_contact_length'] = 'Guardian contact number must be exactly 10 digits.';
$string['alternateemail'] = 'Alternate Email';
