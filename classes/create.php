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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Version details.
 *
 * @package    local_lessonation
 * @author     Yedidia Klein <yedidia@openapp.co.il>
 * @copyright  Yedidia Klein
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_lessonation;

defined('MOODLE_INTERNAL') || die();

class create {
    public static function get_user_data($user_id) {
        global $DB;

        // Check if the user ID is valid.
        if (!$user_id || !is_numeric($user_id)) {
            throw new invalid_parameter_exception('Invalid user ID');
        }

        // Fetch user data from the database.
        $user = $DB->get_record('user', array('id' => $user_id), '*', MUST_EXIST);

        return $user;
    }

    /**
     * Converts JSON data to a lesson object and saves it to the database.
     *
     * @param string $json The JSON data representing the lesson.
     * The JSON should contain the lesson's properties such as name, description, etc. in this format:
     * {
     *     "name": "Lesson Name",
     *     "description": "Lesson Description",
     *     "slide1": {
     *         "title": "Slide 1 Title",
     *         "content": "<h1>Slide 1 Content</h1><ul><li>Item 1.1</li><li>Item 1.2</li></ul>"
     *     },
     *     "slide2": {
     *         "title": "Slide 2 Title",
     *         "content": "<h1>Slide 2 Content</h1><ul><li>Item 2.1</li><li>Item 2.2</li></ul>"
     *     }
     * }
     * @param int $courseid The ID of the course to which the lesson belongs.
     * @return int The ID of the newly created lesson.
     * @throws invalid_parameter_exception If the JSON data is invalid.
     */
    public static function json_to_lesson($json, $courseid) {
        global $DB;

        // Decode the JSON data.
        $data = json_decode($json, true);

        // Check if the JSON data is valid.
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new invalid_parameter_exception('Invalid JSON data');
        }

        // Create a new lesson object.
        $lesson = new \stdClass();
        $lesson->course = $courseid;
        $lesson->name = $data['name'];
        $lesson->description = $data['description'];
        $lesson->timecreated = time();
        $lesson->timemodified = time();
        $lesson->visible = 1; // Set to 1 for visible, 0 for hidden.
        $lesson->conditions = 'O:8:"stdClass":3:{s:9:"timespent";i:0;s:9:"completed";i:0;s:15:"gradebetterthan";i:0;}	'; // Example condition, adjust as needed.
        $lesson->grade = 100; // Set to 0 for no grade, adjust as needed.
        // Add other lesson properties as needed.

        // Insert the lesson into the database.
        $lesson_id = $DB->insert_record('lesson', $lesson);

        // Insert into the modules table.
        $module = new \stdClass();
        $module->course = $courseid;

        // Find the module ID for lessons.
        $moduleid = $DB->get_field('modules', 'id', array('name' => 'lesson'));
        if (!$moduleid) {
            throw new dml_exception('Module not found');
        }
        $module->module = $moduleid;
        $module->instance = $lesson_id;

        // Determine the appropriate section ID.
        $sectionid = $DB->get_field('course_sections', 'id', array('course' => $courseid, 'section' => 0));
        if (!$sectionid) {
            throw new dml_exception('Section not found');
        }
        $module->section = $sectionid;

        $module->added = time();
        $module->visible = 1; // Set to 1 for visible, 0 for hidden.
        $module->visibleold = 1; // Set to 1 for visible, 0 for hidden.
        $module->groupmode = 0; // Set to 0 for no group mode, 1 for groups, 2 for visible groups.
        $module->groupingid = 0; // Set to the appropriate grouping ID.
        $module->completion = 0; // Set to 0 for no completion tracking, adjust as needed.
        $module->completiongradeitemnumber = null; // Set to null for no grade item.
        $module->completionview = 0; // Set to 0 for no view completion, adjust as needed.
        $module->completionexpected = 0; // Set to 0 for no expected completion date, adjust as needed.

        // Insert the module into the course_modules table.
        $module_id = $DB->insert_record('course_modules', $module);

        // Update the context table for the module.
        $context = new \stdClass();
        $context->contextlevel = CONTEXT_MODULE;
        $context->instanceid = $module_id;

        // Fetch the parent context (course context) to set the path.
        $parentcontextid = $DB->get_field('context', 'id', array('contextlevel' => CONTEXT_COURSE, 'instanceid' => $courseid));
        if (!$parentcontextid) {
            throw new dml_exception('Parent context not found');
        }
        $context->path = $DB->get_field('context', 'path', array('id' => $parentcontextid)) . '/' . $module_id;
        $context->depth = substr_count($context->path, '/') + 1;

        // Insert the context record.
        $DB->insert_record('context', $context);

        // Add the module to the course_sections table.
        $sequence = $DB->get_field('course_sections', 'sequence', array('id' => $sectionid));
        $newsequence = $sequence ? $sequence . ',' . $module_id : $module_id;
        $DB->set_field('course_sections', 'sequence', $newsequence, array('id' => $sectionid));

        // Rebuild the course cache to reflect the changes.
        rebuild_course_cache($courseid, true);

        $prev = 0; // Initialize previous page ID for lesson pages.
        // Save slides or other related data as needed to lesson_pages table.
        foreach ($data as $key => $value) {
            if (strpos($key, 'slides') === 0) { // Check if the key starts with 'slide'
                foreach ($value as $vkey => $vvalue) {
                    $slide = new \stdClass();
                    $slide->lessonid = $lesson_id;
                    $slide->title = $vvalue['title'];
                    $slide->contents = $vvalue['content'];
                    $slide->timecreated = time();
                    $slide->timemodified = time();
                    $slide->contentsformat = 1; // Set to 1 for HTML format, adjust as needed.
                    $slide->qtype = 20;
                    $slide->prevpageid = $prev; // Set to 0 for no previous page, adjust as needed.
                    // Insert the slide into the database.
                    $prev = $DB->insert_record('lesson_pages', $slide);
                }

                // Set the next page ID for the all slides.
                $next = 0; // On last slide, set next page ID to 0.
                // Fetch all slides for the lesson to update next page IDs.
                $slides = $DB->get_records('lesson_pages', array('lessonid' => $lesson_id), 'id DESC');
                foreach ($slides as $slide) {
                    $slide->nextpageid = $next;
                    $DB->update_record('lesson_pages', $slide);
                    $next = $slide->id; // Update next page ID for the next iteration.
                }
            }
        }
        // Return the ID of the newly created lesson.
        if ($lesson_id === false) {
            throw new dml_exception('Failed to insert lesson into the database');
            return false;
        } else {
            $lesson_id = (int)$lesson_id;
        }
        return $module_id;
    }

    /**
     * Prepares lesson data for the AI task.
     *
     * @param object $fromform The form data containing the URL and number of slides.
     * @return int The ID of the queued task.
     */
    public static function prepare_lesson_data($fromform) {
        global $USER;

        $num = $fromform->numberofslides;
        $prompt = 'build me a presentation of ' . $num . ' slides based on the content of the following webpage: ' . $fromform->url .
                  ' In the following format :
                { "name": "Lesson Name Test",
                  "description": "Lesson Description Testing",
                  "slides": 
                    [ { "title": "Slide 1 Title",
                        "content": "<h1>Slide 1 Content</h1><ul><li>Item 1</li><li>Item 2</li>li>Item 3</li>li>Item 4</li></ul>" },
                      { "title": "Slide 2 Title",
                        "content": "<h1>Slide 2 Content</h1><ul><li>Item 3</li><li>Item 4</li>li>Item 3</li>li>Item 4</li></ul>" } ]
                }
                Use bullet points in the slides.';

        // Create a new instance of the custom adhoc task.
        $task = new \local_lessonation\task\offlinegen();
        $task->set_custom_data([
            'prompt' => $prompt,
            'courseid' => $fromform->courseid,
            'userid' => $USER->id,
        ]);
        $task->set_component('local_lessonation');
        $task->set_userid($USER->id);
        $task->set_timecreated(time());

        // Queue the task.
        $taskid = \core\task\manager::queue_adhoc_task($task);

        return $taskid;
    }

}