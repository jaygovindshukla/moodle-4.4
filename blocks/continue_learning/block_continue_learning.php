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

class block_continue_learning extends block_base {
    public function init(): void {
        $this->title = get_string('pluginname', 'block_continue_learning');
    }

    public function has_config(): bool {
        return true;
    }

    public function applicable_formats(): array {
        return [
            'all' => true,
            'site' => true,
            'my' => true,
            'course-view' => true,
        ];
    }

    public function get_content() {
        global $PAGE, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        if (!isloggedin() || isguestuser()) {
            $this->content->text = '';
            return $this->content;
        }

        $context = context_block::instance($this->instance->id);
        if (!has_capability('block/continue_learning:view', $context, $USER)) {
            $this->content->text = '';
            return $this->content;
        }

        $renderer = $PAGE->get_renderer('block_continue_learning');
        $this->content->text = $renderer->render_block_content();
        $this->content->footer = '';

        $PAGE->requires->js_call_amd('block_continue_learning/main', 'init');

        return $this->content;
    }
}
