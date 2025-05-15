define(['jquery', 'core/ajax'], function($, ajax) {
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
                        // Lesson is ready.
                        gifContainer.hide();
                        statusContainer.html('<div class="alert alert-success">Lesson is ready! ' +
                            '<a href="' + M.cfg.wwwroot + '/mod/lesson/view.php?id=' + response[0].lessonid +
                            '">Click here to view the lesson</a></div>');
                    } else {
                        // Lesson is still being prepared, check again after 5 seconds.
                        setTimeout(checkLessonState, 5000);
                    }
                }).fail(function() {
                    gifContainer.hide();
                    statusContainer.html('<div class="alert alert-danger">Error checking lesson state.</div>');
                });
            }

            // Start checking the lesson state.
            checkLessonState();
        }
    };
});