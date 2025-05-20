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
 * Service for checking state of lesson generation.
 *
 * @package     local_lessonation
 * @category    admin
 * @copyright   Yedidia Klein <yedidia@openapp.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_lessonation;
use local_lessonation\create;

/**
 * Unit tests for the create class in the Lessonation plugin.
 *
 * @package    local_lessonation
 * @category   test
 * @group      local_lessonation
 */
final class create_test extends advanced_testcase {

    /**
     * Test the json_to_lesson function.
     * @covers ::json_to_lesson
     * @return void
     */
    public function test_json_to_lesson(): void {
        global $DB;

        // Reset the database after the test.
        $this->resetAfterTest();

        // Create a test course.
        $course = $this->getDataGenerator()->create_course();

        // Example JSON data for a lesson.
        $json = '{
            "name": "History of the State of Israel",
            "description": "An overview of key events in the history of the State of Israel from ancient times to the modern era.",
            "slides": [
              {
                "title": "Ancient Roots",
                "content": "<h1>Ancient Roots</h1><ul>
                <li>The land of Israel is central to Jewish history and religion.</li>
                <li>Kingdoms of Israel and Judah existed in the region over 3,000 years ago.</li></ul>"
              },
              {
                "title": "Roman Rule and Diaspora",
                "content": "<h1>Roman Rule and Diaspora</h1><ul>
                <li>The Romans conquered the region and renamed it Judea, later Palestine.</li>
                <li>Jewish revolts led to mass dispersal of Jews across the world.</li></ul>"
              },
              {
                "title": "Zionist Movement",
                "content": "<h1>Zionist Movement</h1><ul>
                <li>Late 1800s: Jews began advocating for a return to their ancestral homeland.</li>
                <li>Theodor Herzl is considered the father of modern Zionism.</li></ul>"
              },
              {
                "title": "British Mandate Period",
                "content": "<h1>British Mandate Period</h1><ul>
                <li>Post-WWI: Britain took control of Palestine under the League of Nations mandate.</li>
                <li>Conflicts grew between Jews and Arabs over land and immigration.</li></ul>"
              },
              {
                "title": "UN Partition Plan",
                "content": "<h1>UN Partition Plan</h1><ul>
                <li>In 1947, the United Nations proposed partitioning Palestine into Jewish and Arab states.</li>
                <li>The plan was accepted by Jews but rejected by Arab states.</li></ul>"
              },
              {
                "title": "Declaration of Independence",
                "content": "<h1>Declaration of Independence</h1><ul>
                <li>On May 14, 1948, David Ben-Gurion declared the establishment of the State of Israel.</li>
                <li>Immediately after, neighboring Arab countries invaded.</li></ul>"
              },
              {
                "title": "1948 Arab-Israeli War",
                "content": "<h1>1948 Arab-Israeli War</h1><ul>
                <li>Israel survived the war and gained more territory than allocated in the UN plan.</li>
                <li>Hundreds of thousands of Palestinian Arabs became refugees.</li></ul>"
              },
              {
                "title": "Mass Immigration and Growth",
                "content": "<h1>Mass Immigration and Growth</h1><ul>
                <li>Jews from around the world immigrated to Israel, especially from Europe and Arab countries.</li>
                <li>New towns and infrastructure were rapidly built.</li></ul>"
              },
              {
                "title": "Wars and Peace Efforts",
                "content": "<h1>Wars and Peace Efforts</h1><ul>
                <li>Major conflicts included the Six-Day War (1967) and Yom Kippur War (1973).</li>
                <li>Peace treaties signed with Egypt (1979) and Jordan (1994).</li></ul>"
              },
              {
                "title": "Modern Israel",
                "content": "<h1>Modern Israel</h1><ul>
                <li>Israel is a democratic and technologically advanced country.</li>
                <li>Ongoing challenges include regional tensions and efforts for peace with the Palestinians.</li></ul>"
              }
            ]
          }';

        // Call the json_to_lesson function.
        $create = new create();
        $lessonid = $create->json_to_lesson($json, $course->id, 0);

        echo "Lesson created successfully with ID: $lessonid\n";

        // Assert that the lesson was created.
        $this->assertNotEmpty($lessonid);

        // Verify the lesson exists in the database.
        $lesson = $DB->get_record('lesson', ['id' => $lessonid]);
        $this->assertNotEmpty($lesson);
        $this->assertEquals('Test Lesson', $lesson->name);
    }

    /**
     * Test the prepare_lesson_data function.
     *
     * @covers ::prepare_lesson_data
     * @return void
     */
    public function test_prepare_lesson_data(): void {
        global $USER;

        // Reset the database after the test.
        $this->resetAfterTest();

        // Create a test course.
        $course = $this->getDataGenerator()->create_course();
        // Create a module in course for having section(s).
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);

        // Simulate form data.
        $fromform = (object)[
            'url' => 'https://en.wikipedia.org/wiki/Moodle_(software)',
            'numberofslides' => 5,
            'sectionid' => 0,
            'courseid' => $course->id,
        ];

        // Call the prepare_lesson_data function.
        $create = new create();
        $result = $create->prepare_lesson_data($fromform);

        // Assert that the result is true (task queued successfully).
        $this->assertTrue($result);
    }
}
