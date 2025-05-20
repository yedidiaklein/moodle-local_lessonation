define(['jquery', 'core/ajax', 'core/str'], function($, ajax, str) {
    return {
        init: function(adhocId) {
            const gifContainer = $('#preparing-gif-container');
            const statusContainer = $('#status-container');

            // Show the "preparing" GIF.
            gifContainer.show();

            // Function to check the lesson state.
            function checkLessonState() {
                ajax.call([{
                    methodname: 'local_lessonation_check_state', // Web service function name.
                    args: { adhocid: adhocId }
                }])[0].done(function(response) {
                    if (response[0].state === 1) {
                        // Fetch Moodle strings.
                        str.get_strings([
                            {key: 'lessoncreated', component: 'local_lessonation'},
                            {key: 'clickhere', component: 'local_lessonation'}
                        ]).done(function(strings) {
                            gifContainer.hide();
                            statusContainer.html('<div class="alert alert-success">' +
                                strings[0] + ' <a href="' + M.cfg.wwwroot + '/mod/lesson/view.php?id=' +
                                response[0].lessonid + '">' + strings[1] + '</a></div>');
                        });
                    } else {
                        // Lesson is still being prepared, check again after 5 seconds.
                        setTimeout(checkLessonState, 5000);
                    }
                }).fail(function() {
                    // Fetch error string.
                    str.get_string('error_checking_state', 'local_lessonation').done(function(errorString) {
                        gifContainer.hide();
                        statusContainer.html('<div class="alert alert-danger">' + errorString + '</div>');
                    });
                });
            }

            // Start checking the lesson state.
            checkLessonState();
        }
    };
});