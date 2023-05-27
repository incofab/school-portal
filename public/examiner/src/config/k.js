
if (window.dev === undefined) window.dev = true;

export const BASE_ADDR = process.env.REACT_APP_BASE_URL;

const K = {

    ACTION_TAB_CHANGED: 'Nav Tab Changed',
    ACTION_ANSWER_SELECTED: 'Answer Selected',
    ACTION_QUESTION_NAVIGATED: 'Question Navigated',
    ACTION_ANSWERS_UPLOADED: 'Answers Uploaded', // To be called when selected answers has been uploaded to server
    ACTION_TOGGLE_CALCULATOR: 'Toggle Calculator',
    ACTION_PAUSE_EXAM: 'Pause Exam',
    ACTION_PLAY_EXAM: 'Play Exam',

    EXAM_STATE_PAUSED: 'Paused',
    EXAM_STATE_PLAYING: 'Playing',

    ADDR_ATTEMPT_QUESTION: BASE_ADDR + process.env.REACT_APP_ADDR_ATTEMPT_QUESTION,
    ADDR_PAUSE_EXAM: BASE_ADDR + process.env.REACT_APP_ADDR_PAUSE_EXAM,
    ADDR_SUBMIT_EXAM: BASE_ADDR + process.env.REACT_APP_ADDR_SUBMIT_EXAM,
    
    ADDR_EXAM_BASE_IMG: BASE_ADDR + process.env.REACT_APP_ADDR_EXAM_BASE_IMG,
    ADDR_VIEW_EXAM_RESULT: BASE_ADDR + process.env.REACT_APP_ADDR_VIEW_EXAM_RESULT,

    getImageAddr(courseId, course_session_id, fileName, year) {
        var imgAddr = K.ADDR_EXAM_BASE_IMG
            +`?course_id=${courseId}&course_session_id=${course_session_id}`
            +`&filename=${fileName}&session=${year}&event_id=${window.event_id}`;
            
        return imgAddr;
    },

    handleExamImgs(html, subject) {   
        // return html;     
        var $htmlparsed = window.$(window.$.parseHTML('<div>'+html+'</div>'));
        
        // window.$($htmlparsed).find('img').each(function(i, ele) {
        $htmlparsed.find('img').each(function(i, ele) {
            var $img = window.$(ele);
            var src = $img.attr('src');
            // var alt = $img.attr('alt');

            var imgAddr = K.getImageAddr(subject.course_id, subject.session_id,
                src, subject.year);

            $img.attr('src', imgAddr);
        });

        return $htmlparsed.html()+"";
    },

    formatTime(time_in_secs) {

        if (isNaN(time_in_secs) || time_in_secs < 0) time_in_secs = 0

        var total_mins = time_in_secs / 60;
        var hour = parseInt((total_mins / 60) + '');
        var min = parseInt((total_mins % 60) + '');
        var sec = parseInt((time_in_secs % 60) + '');
        sec = sec < 10 ? ('0' + sec) : sec;

        if (hour < 1 && min < 1) return sec;

        min = min < 10 ? ('0' + min) : min;

        if (hour < 1) return min + ':' + sec;

        hour = hour < 10 ? ('0' + hour) : hour;

        return hour + ':' + min + ':' + sec;
    }

}

export default K;