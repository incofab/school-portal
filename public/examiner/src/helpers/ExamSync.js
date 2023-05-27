import K from '../config/k'
import Data from '../config/startup'

class ExamSync{

    attemptsToUpload = {};
    // attemptsUploading = {};

    addAttempt(attemptData){
        this.attemptsToUpload[attemptData.question_id] = attemptData;
    }
    
    addMultiAttempts(multipleAttemtps){
        for (const questionId in multipleAttemtps) {
            if (Object.hasOwnProperty.call(multipleAttemtps, questionId)) {
                const attempt = multipleAttemtps[questionId];
                if(this.attemptsToUpload.question_id == attempt.question_id)
                    continue;
                this.attemptsToUpload[questionId] = attempt;
            }
        }
    }

    uploadNow(){
        if(JSON.stringify(this.attemptsToUpload) === '{}'){
            return;
        }
        var attemptsUploading = this.attemptsToUpload;
        this.attemptsToUpload = {};

        var data = {
            'attempts': attemptsUploading,
            'user_id': Data.data.user_id,
            'exam_no': Data.data.exam_no,
            'event_id': window.event_id,
            'student_id': Data.data.student_id,
            'token': Data.data.token,
        }

        var url = K.ADDR_ATTEMPT_QUESTION;

        fetch(url, {
            method: 'POST', // or 'PUT'
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        })
        .then(response => {
            console.log(response); 
            return response.json();
        })
        .then(data => {
            console.log('uploadNow() Success:', data);
            attemptsUploading = {};
        })
        .catch((error) => {
            console.error('uploadNow() Error:', error);
            this.addMultiAttempts(attemptsUploading);
        });
    }

    pause(){
        var attemptsUploading = this.attemptsToUpload;
        this.attemptsToUpload = {};

        var data = {
            'attempts': attemptsUploading,
            'user_id': Data.data.user_id,
            'exam_no': Data.data.exam_no,
            'event_id': window.event_id,
            'student_id': Data.data.student_id,
            'token': Data.data.token,
        }

        fetch(K.ADDR_PAUSE_EXAM, {
            method: 'POST', // or 'PUT'
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        })
        .then(response => response.json())
        .then(data => {
            attemptsUploading = {};
            console.log('pause() Success:', data);
            window.location.href = data.redirect ? data.redirect : K.BASE_ADDR;
        })
        .catch((error) => {
            console.error('pause() Error:', error);
            this.addMultiAttempts(attemptsUploading);
        });
    }

    submit(){
        var attemptsUploading = this.attemptsToUpload;
        this.attemptsToUpload = {};

        var postData = {
            'attempts': attemptsUploading,
            'user_id': Data.data.user_id,
            'exam_no': Data.data.exam_no,
            'event_id': window.event_id,
            'student_id': Data.data.student_id,
            'token': Data.data.token,
        }

        var url = K.ADDR_SUBMIT_EXAM;

        fetch(url, {
            method: 'POST', // or 'PUT'
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(postData),
        })
        .then(response => response.json())
        .then(data => {
            attemptsUploading = {};
            console.log('submit() Success:', data);
            window.location.href = K.ADDR_VIEW_EXAM_RESULT+'/'+postData.exam_no;
            // if(data.redirect)
            //     window.location.href = data.redirect;
            // else window.location.href = K.BASE_ADDR;
        })
        .catch((error) => {
            console.error('submit() Error:', error);
            console.log(error);
            this.addMultiAttempts(attemptsUploading);
        });
    }



}

const examSync = new ExamSync();

export default examSync;