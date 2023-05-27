import TimerHandler from './TimerHandler'

class ExamHandler{

    attempted_questions_to_upload = [  // Contains an array of attempts
        { //Attempts should look like this
            exam_subject_id:'',
            question_id: '',
            attempt: ''
        }
    ];

    timerHandler;

    constructor(){
        // console.log("ExamHandler constructor called, Rand "+Math.random());
        this.timerHandler = new TimerHandler();
    }

    addToAttemptedQuestions = (newAttempt) => {
        this.attempted_questions_to_upload.filter((attempt) => {
            return attempt.exam_subject_id != newAttempt.exam_subject_id
                || attempt.question_id != newAttempt.question_id;
        });

        this.attempted_questions_to_upload.push(newAttempt);
    }
    
}

const examHandler = new ExamHandler();

export default examHandler;