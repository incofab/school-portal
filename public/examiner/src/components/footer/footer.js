import {connect} from 'react-redux'
import K from '../../config/k'
import Data from '../../config/startup'
import examSync from '../../helpers/ExamSync'

var gProps;

const getCurrentQuestionIndex = (tabIndex) => {
    let subjectData = gProps.all_exam_subjects_state_data[tabIndex];
    return subjectData.current_question_index;
}

const questionNoSelected = (question_index) => {
    var tabIndex = gProps.current_tab;

    if(question_index < 0) return;
    if(question_index >= Data.exam_data.all_exam_subject_data[tabIndex]
        .questions.length) return;

    gProps.dispatch({
        type: K.ACTION_QUESTION_NAVIGATED,
        payload: {
            question_index: question_index,
            tab_index: tabIndex,
        }
    }); 
}

const gotoNextQuestion = (e) => {
    var questionIndex = getCurrentQuestionIndex(gProps.current_tab) + 1;
    questionNoSelected(questionIndex);
}

const gotoPreviousQuestion = (e) => {
    var questionIndex = getCurrentQuestionIndex(gProps.current_tab) - 1;
    questionNoSelected(questionIndex);
}

const submit = (e) => {
    
    if(!window.confirm('Do you want to submit and end this exam?')) return;

    examSync.submit();
}

const footer = (props) => {
    
    gProps = props;

    return (
        <div className="question-nav text-center clearfix">
            <button className="btn btn-primary float-left" id="previous-question"
                onClick={gotoPreviousQuestion}
                style={{width: '100px'}}>&laquo; Previous</button>
            <button className="btn btn-primary float-right" id="next-question"
                onClick={gotoNextQuestion}
                style={{width: '100px'}}>Next &raquo;</button>
            <button className="btn btn-primary mx-auto px-3" id="stop-exam"
                onClick={submit}
                data-toggle="tooltip" data-placement="top" title="Submit and end this exam. Cannot be resumed" >
                <i className="fa fa-paper-plane"></i> Submit
            </button>
        </div>
    )
}

const mapStateToProps = (state) => {
    return {
        current_tab: state.current_tab,
        all_exam_subjects_state_data: state.all_exam_subjects_state_data,
    }
}
export default connect(mapStateToProps)(footer);
