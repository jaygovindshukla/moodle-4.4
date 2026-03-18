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

namespace block_continue_learning\output;

use renderer_base;
use core_course_list_element;
use core_completion\progress;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/completionlib.php');

class renderer extends renderer_base {
    public function render_block_content(): string {
        global $USER;

        $limit = (int) get_config('block_continue_learning', 'limit');
        if ($limit <= 0) {
            $limit = 6;
        }
        $showprogress = (bool) get_config('block_continue_learning', 'showprogress');
        $showimages = (bool) get_config('block_continue_learning', 'showimages');

        $courses = enrol_get_users_courses($USER->id, true, '*', 'visible DESC, sortorder ASC');
        $courses = array_values($courses);
        if ($limit > 0 && count($courses) > $limit) {
            $courses = array_slice($courses, 0, $limit);
        }

        $carddata = [];
        foreach ($courses as $course) {
            if ($course->id == SITEID) {
                continue;
            }
            $context = \context_course::instance($course->id);
            if (!is_enrolled($context, $USER, '', true)) {
                continue;
            }

            $url = new moodle_url('/course/view.php', ['id' => $course->id]);
            $courselistelement = new core_course_list_element($course);

            $imageurl = null;
            if ($showimages) {
                foreach ($courselistelement->get_course_overviewfiles() as $file) {
                    if ($file->is_valid_image()) {
                        $imageurl = moodle_url::make_pluginfile_url(
                            $file->get_contextid(),
                            $file->get_component(),
                            $file->get_filearea(),
                            $file->get_itemid(),
                            $file->get_filepath(),
                            $file->get_filename()
                        );
                        break;
                    }
                }
            }

            $progressvalue = 0;
            if ($showprogress) {
                $percentage = progress::get_course_progress_percentage($course, $USER->id);
                if ($percentage !== null) {
                    $progressvalue = (int) round($percentage);
                }
            }

            $carddata[] = [
                'name' => format_string($course->fullname, true, ['context' => $context]),
                'url' => $url->out(false),
                'imageurl' => $imageurl ? $imageurl->out(false) : null,
                'progress' => $progressvalue,
                'showimages' => $showimages,
                'showprogress' => $showprogress,
            ];
        }

        $data = new stdClass();
        $data->hascourses = !empty($carddata);
        $data->courses = $carddata;
        $data->showimages = $showimages;
        $data->showprogress = $showprogress;
        $data->mycoursesurl = (new moodle_url('/my/courses.php'))->out(false);

        return $this->render_from_template('block_continue_learning/content', $data);
    }
}
