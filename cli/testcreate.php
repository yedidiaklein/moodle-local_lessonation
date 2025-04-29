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
 * Version details.
 *
 * @package    local_lessonation
 * @author     Yedidia Klein <yedidia@openapp.co.il>
 * @copyright  Yedidia Klein
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// CLI script for Lessonation plugin.
// This script is used to create a lesson from JSON data.
// It should be run from the command line and requires the Moodle environment to be set up.
// This script is intended for testing purposes and should not be used in production without proper validation and error handling.

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/lessonation/classes/create.php');

$json = file_get_contents('basic_mathematics_100_real_slides.json');
$courseid = 2; // Example course ID.
$lessonation = new \local_lessonation\create();
$lessonid = $lessonation->json_to_lesson($json, $courseid);
if ($lessonid) {
    echo "Lesson created successfully with ID: $lessonid\n";
} else {
    echo "Failed to create lesson.\n";
}