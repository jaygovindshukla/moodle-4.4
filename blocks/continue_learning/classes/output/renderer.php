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
use core_course\external\course_summary_exporter;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/completionlib.php');

class renderer extends renderer_base {
    public function render_block_content(): string {
        global $USER;

        $courses = enrol_get_users_courses($USER->id, true, '*', 'visible DESC, sortorder ASC');
        $courses = array_values($courses);
        $categorydata = [];
        foreach ($courses as $course) {
            if ($course->id == SITEID) {
                continue;
            }
            $context = \context_course::instance($course->id);
            if (!is_enrolled($context, $USER, '', true)) {
                continue;
            }

            $categoryid = (int) $course->category;
            if ($categoryid <= 0) {
                continue;
            }

            if (!isset($categorydata[$categoryid])) {
                $category = \core_course_category::get($categoryid, IGNORE_MISSING);
                if (!$category) {
                    continue;
                }

                $categoryurl = new moodle_url('/blocks/continue_learning/category.php', ['categoryid' => $categoryid]);
                $categorydata[$categoryid] = [
                    'id' => $categoryid,
                    'name' => $category->get_formatted_name(),
                    'coursecount' => 0,
                    'url' => $categoryurl->out(false),
                ];
            }

            $categorydata[$categoryid]['coursecount']++;
        }

        $data = new stdClass();
        $data->hascategories = !empty($categorydata);
        $data->categories = array_values($categorydata);

        return $this->render_from_template('block_continue_learning/content', $data);
    }

    /**
     * Render category page with enrolled courses.
     *
     * @param \core_course_category $category
     * @param array $courses
     * @return string
     */
    public function render_category_courses_page(\core_course_category $category, array $courses): string {
        global $USER;

        $showprogress = (bool) get_config('block_continue_learning', 'showprogress');
        $showimages = (bool) get_config('block_continue_learning', 'showimages');

        $cards = [];
        foreach ($courses as $course) {
            $context = \context_course::instance($course->id);
            $cards[] = $this->build_course_card(
                $course,
                $context,
                $showimages,
                $showprogress,
                $USER->id
            );
        }

        $data = new stdClass();
        $data->categoryname = $category->get_formatted_name();
        $data->hascourses = !empty($cards);
        $data->courses = $cards;

        return $this->render_from_template('block_continue_learning/category_page', $data);
    }

    /**
     * Build display data for one course card.
     *
     * @param \stdClass $course
     * @param \context_course $context
     * @param bool $showimages
     * @param bool $showprogress
     * @param int $userid
     * @return array
     */
    private function build_course_card(
        \stdClass $course,
        \context_course $context,
        bool $showimages,
        bool $showprogress,
        int $userid
    ): array {
        $url = new moodle_url('/course/view.php', ['id' => $course->id]);
        $courselistelement = new core_course_list_element($course);

        $imageurl = null;
        if ($showimages) {
            $cached = course_summary_exporter::get_course_image($course);
            if (!empty($cached)) {
                $imageurl = $cached;
            } else {
                $file = course_get_courseimage($course);
                if ($file) {
                    $imageurl = moodle_url::make_pluginfile_url(
                        $file->get_contextid(),
                        $file->get_component(),
                        $file->get_filearea(),
                        null,
                        $file->get_filepath(),
                        $file->get_filename()
                    )->out(false);
                } else {
                    foreach ($courselistelement->get_course_overviewfiles() as $overviewfile) {
                        if ($overviewfile->is_valid_image()) {
                            $imageurl = moodle_url::make_pluginfile_url(
                                $overviewfile->get_contextid(),
                                $overviewfile->get_component(),
                                $overviewfile->get_filearea(),
                                null,
                                $overviewfile->get_filepath(),
                                $overviewfile->get_filename()
                            )->out(false);
                            break;
                        }
                    }
                }
            }
        }

        $progressvalue = 0;
        if ($showprogress) {
            $percentage = progress::get_course_progress_percentage($course, $userid);
            if ($percentage !== null) {
                $progressvalue = (int) round($percentage);
            }
        }

        return [
            'name' => format_string($course->fullname, true, ['context' => $context]),
            'url' => $url->out(false),
            'imageurl' => $imageurl,
            'progress' => $progressvalue,
            'showimages' => $showimages,
            'showprogress' => $showprogress,
        ];
    }
}
