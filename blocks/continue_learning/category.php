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

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

$categoryid = required_param('categoryid', PARAM_INT);

require_login();

$category = core_course_category::get($categoryid, MUST_EXIST);
$courses = enrol_get_users_courses($USER->id, true, '*', 'visible DESC, sortorder ASC');
$courses = array_values(array_filter($courses, static function($course) use ($categoryid): bool {
    return (int) $course->id !== SITEID && (int) $course->category === $categoryid;
}));
if (empty($courses)) {
    print_error('nopermissions', 'error');
}

$url = new moodle_url('/blocks/continue_learning/category.php', ['categoryid' => $categoryid]);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'block_continue_learning'));
$PAGE->set_heading(format_string($category->name));
$PAGE->requires->css('/blocks/continue_learning/styles.css');

$renderer = $PAGE->get_renderer('block_continue_learning');

echo $OUTPUT->header();
echo $renderer->render_category_courses_page($category, $courses);
echo $OUTPUT->footer();
