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
        global $DB, $USER;

        $limit = (int) get_config('block_continue_learning', 'limit');
        if ($limit <= 0) {
            $limit = 6;
        }
        $showprogress = (bool) get_config('block_continue_learning', 'showprogress');
        $showimages = (bool) get_config('block_continue_learning', 'showimages');
        $selectedcategoryid = optional_param('clcategory', 0, PARAM_INT);

        $categorysql = "SELECT id, name, sortorder
                          FROM {course_categories}
                         WHERE visible = 1
                      ORDER BY sortorder ASC";
        $allcategories = $DB->get_records_sql($categorysql);

        $coursesql = "SELECT *
                        FROM {course}
                       WHERE id <> :siteid
                         AND visible = 1
                    ORDER BY category ASC, sortorder ASC";
        $courses = $DB->get_records_sql($coursesql, ['siteid' => SITEID]);
        $courses = array_values($courses);

        $categorycourses = [];
        $categorycounts = [];
        foreach ($courses as $course) {
            $context = \context_course::instance($course->id);
            $categoryid = (int) $course->category;
            if ($categoryid <= 0) {
                continue;
            }

            $categorycounts[$categoryid] = ($categorycounts[$categoryid] ?? 0) + 1;

            if ($limit > 0 && isset($categorycourses[$categoryid]) && count($categorycourses[$categoryid]) >= $limit) {
                continue;
            }

            $categorycourses[$categoryid][] = $this->build_course_card(
                $course,
                $context,
                $showimages,
                $showprogress,
                $USER->id
            );
        }

        $categorydata = [];
        $selectedfound = false;
        foreach ($allcategories as $category) {
            $categoryid = (int) $category->id;
            $categorycontext = \context_coursecat::instance($categoryid);
            $categoryname = format_string($category->name, true, ['context' => $categorycontext]);

            $categoryurl = new moodle_url($this->page->url, ['clcategory' => $categoryid]);
            $isselected = $selectedcategoryid === $categoryid;
            if ($isselected) {
                $selectedfound = true;
            }
            $categorydata[] = [
                'id' => $categoryid,
                'name' => $categoryname,
                'coursecount' => $categorycounts[$categoryid] ?? 0,
                'url' => $categoryurl->out(false),
                'selected' => $isselected,
            ];
        }

        if ((!$selectedfound || $selectedcategoryid <= 0) && !empty($categorydata)) {
            $selectedcategoryid = (int) $categorydata[0]['id'];
            foreach ($categorydata as $index => $category) {
                $categorydata[$index]['selected'] = ($index === 0);
            }
        }

        $data = new stdClass();
        $data->hascategories = !empty($categorydata);
        $data->categories = $categorydata;
        $data->selectedcourses = $categorycourses[$selectedcategoryid] ?? [];
        $data->hasselectedcourses = !empty($data->selectedcourses);
        $data->showimages = $showimages;
        $data->showprogress = $showprogress;

        return $this->render_from_template('block_continue_learning/content', $data);
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
