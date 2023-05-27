import K from '../config/k'
import Data from '../config/startup'

class ExamActions{

    gotoNextQuestion = (store) => {
        let currentState = store.getState();
        var questionIndex = this.getCurrentQuestionIndex(currentState) + 1;
        this.questionNoSelected(store, questionIndex);
    }

    gotoPreviousQuestion = (store) => {
        let currentState = store.getState();
        var questionIndex = this.getCurrentQuestionIndex(currentState) - 1;
        this.questionNoSelected(store, questionIndex);
    }
    
    answerSelected = (store, option) => {
        let currentState = store.getState();

        let tabIndex = currentState.current_tab;
        var questionIndex = this.getCurrentQuestionIndex(currentState);
        let subject = Data.exam_data.all_exam_subject_data[tabIndex];
        
        let question_id = subject.questions[questionIndex].question_id;
        let exam_subject_id = subject.exam_subject_id;

        store.dispatch({
            type: K.ACTION_ANSWER_SELECTED,
            payload: {
                'tab_index':currentState.current_tab,
                'exam_subject_id':exam_subject_id,
                'question_id':question_id,
                'attempt':option,
            }
        });
    }




    getCurrentQuestionIndex = (currentState) => {
        let tabIndex = currentState.current_tab;
        let subjectData = currentState.all_exam_subjects_state_data[tabIndex];
        return subjectData.current_question_index;
    }

    questionNoSelected = (store, question_index) => {
        let currentState = store.getState();
        var tabIndex = currentState.current_tab;
        // console.log('currentState = ', currentState);
        // console.log('tabIndex = ', tabIndex);
        if(question_index < 0) return;
        if(question_index >= Data.exam_data.all_exam_subject_data[tabIndex]
            .questions.length) return;

        store.dispatch({
            type: K.ACTION_QUESTION_NAVIGATED,
            payload: {
                question_index: question_index,
                tab_index: tabIndex,
            }
        }); 
    }












}

const examActions = new ExamActions();

export default examActions;