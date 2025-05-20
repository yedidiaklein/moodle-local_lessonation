<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * class for creation functions.
 *
 * @package     local_lessonation
 * @category    admin
 * @copyright   Yedidia Klein <yedidia@openapp.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_lessonation;

/**
 * Class responsible for creating lessons in the Lessonation plugin.
 *
 * @package    local_lessonation
 */
class create {

    /**
     * Retrieves user data from the database.
     *
     * @param int $userid The ID of the user.
     * @return object The user data.
     * @throws \invalid_parameter_exception If the user ID is invalid.
     */
    public static function get_user_data($userid) {
        global $DB;

        // Check if the user ID is valid.
        if (!$userid || !is_numeric($userid)) {
            throw new \invalid_parameter_exception('Invalid user ID.');
        }

        // Fetch user data from the database.
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

        return $user;
    }

    /**
     * Converts JSON data to a lesson object and saves it to the database.
     *
     * @param string $json The JSON data representing the lesson.
     * @param int $courseid The ID of the course to which the lesson belongs.
     * @param int $sectionid The ID of the course section.
     * @return int The ID of the newly created lesson module.
     * @throws \invalid_parameter_exception If the JSON data is invalid.
     */
    public static function json_to_lesson($json, $courseid, $sectionid) {
        global $DB;

        // Decode the JSON data.
        $data = json_decode($json, true);

        // Check if the JSON data is valid.
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \invalid_parameter_exception('Invalid JSON data.');
        }

        // Create a new lesson object.
        $lesson = new \stdClass();
        $lesson->course = $courseid;
        $lesson->name = $data['name'];
        $lesson->description = $data['description'];
        $lesson->timecreated = time();
        $lesson->timemodified = time();
        $lesson->visible = 1; // Set to 1 for visible, 0 for hidden.
        $lesson->conditions = 'O:8:"stdClass":3:{s:9:"timespent";i:0;s:9:"completed";i:0;s:15:"gradebetterthan";i:0;}';
        $lesson->grade = 100; // Set to 0 for no grade.

        // Insert the lesson into the database.
        $lessonid = $DB->insert_record('lesson', $lesson);

        // Insert into the modules table.
        $module = new \stdClass();
        $module->course = $courseid;

        // Find the module ID for lessons.
        $moduleid = $DB->get_field('modules', 'id', ['name' => 'lesson']);
        if (!$moduleid) {
            throw new \dml_exception('Module not found.');
        }
        $module->module = $moduleid;
        $module->instance = $lessonid;
        $module->section = $sectionid;
        $module->added = time();
        $module->visible = 1;
        $module->visibleold = 1;
        $module->groupmode = 0;
        $module->groupingid = 0;
        $module->completion = 0;
        $module->completiongradeitemnumber = null;
        $module->completionview = 0;
        $module->completionexpected = 0;

        // Insert the module into the course_modules table.
        $moduleid = $DB->insert_record('course_modules', $module);

        // Update the context table for the module.
        $context = new \stdClass();
        $context->contextlevel = CONTEXT_MODULE;
        $context->instanceid = $moduleid;

        // Fetch the parent context (course context) to set the path.
        $parentcontextid = $DB->get_field('context', 'id', ['contextlevel' => CONTEXT_COURSE, 'instanceid' => $courseid]);
        if (!$parentcontextid) {
            throw new \dml_exception('Parent context not found.');
        }
        $context->path = $DB->get_field('context', 'path', ['id' => $parentcontextid]) . '/' . $moduleid;
        $context->depth = substr_count($context->path, '/') + 1;

        // Insert the context record.
        $DB->insert_record('context', $context);

        // Add the module to the course_sections table.
        $sequence = $DB->get_field('course_sections', 'sequence', ['id' => $sectionid]);
        $newsequence = $sequence ? $sequence . ',' . $moduleid : $moduleid;
        $DB->set_field('course_sections', 'sequence', $newsequence, ['id' => $sectionid]);

        // Rebuild the course cache to reflect the changes.
        rebuild_course_cache($courseid, true);

        $prev = 0; // Initialize previous page ID for lesson pages.
        // Save slides or other related data as needed to lesson_pages table.
        foreach ($data as $key => $value) {
            if (strpos($key, 'slides') === 0) { // Check if the key starts with 'slides'.
                foreach ($value as $vkey => $vvalue) {
                    $slide = new \stdClass();
                    $slide->lessonid = $lessonid;
                    $slide->title = $vvalue['title'];
                    $slide->contents = $vvalue['content'];
                    $slide->timecreated = time();
                    $slide->timemodified = time();
                    $slide->contentsformat = 1; // Set to 1 for HTML format.
                    $slide->qtype = 20;
                    $slide->prevpageid = $prev; // Set to 0 for no previous page.
                    // Insert the slide into the database.
                    $prev = $DB->insert_record('lesson_pages', $slide);
                }

                // Set the next page ID for all slides.
                $next = 0; // On last slide, set next page ID to 0.
                $slides = $DB->get_records('lesson_pages', ['lessonid' => $lessonid], 'id DESC');
                foreach ($slides as $slide) {
                    $slide->nextpageid = $next;
                    $DB->update_record('lesson_pages', $slide);
                    $next = $slide->id; // Update next page ID for the next iteration.
                }
            }
        }

        // Return the ID of the newly created lesson module.
        return $moduleid;
    }

    /**
     * Prepares lesson data for the AI task.
     *
     * @param object $fromform The form data containing the URL and number of slides.
     * @return int The ID of the queued task.
     */
    public static function prepare_lesson_data($fromform) {
        global $USER;

        // Form start num of slides from 0.
        $num = $fromform->numberofslides + 1;

        // Check if URL is a valid URL or text.
        if (filter_var($fromform->url, FILTER_VALIDATE_URL) === false) {
            $promptsubject = ' following subject: ' . $fromform->url . '. ';
        } else {
            $promptsubject = ' following webpage: ' . $fromform->url . '. ';
        }
        $prompt = 'Build me a presentation of ' . $num . ' slides based on the content of the ' .
                  $promptsubject .
                  ' In the following format:
                { "name": "Lesson Name Test",
                  "description": "Lesson Description Testing",
                  "slides":
                    [ { "title": "Slide 1 Title",
                        "content": "<h1>Slide 1 Content</h1><ul><li>Item 1</li><li>Item 2</li>
                        <li>Item 3</li><li>Item 4</li></ul>" },
                      { "title": "Slide 2 Title",
                        "content": "<h1>Slide 2 Content</h1><ul><li>Item 3</li><li>Item 4</li>
                        <li>Item 5</li><li>Item 6</li></ul>" } ]
                }
                Use bullet points in the slides.';

        // Create a new instance of the custom adhoc task.
        $task = new \local_lessonation\task\offlinegen();
        $task->set_custom_data([
            'prompt' => $prompt,
            'courseid' => $fromform->courseid,
            'userid' => $USER->id,
            'sectionid' => $fromform->sectionid,
        ]);
        $task->set_component('local_lessonation');
        $task->set_userid($USER->id);
        $task->set_timecreated(time());

        // Queue the task.
        $taskid = \core\task\manager::queue_adhoc_task($task);

        return $taskid;
    }
}
