import Data from '../../../config/startup'
import K from '../../../config/k'
import {connect} from 'react-redux'
import SubjectPage from './subject_page'
import {
    OBJECTIVE_QUESTION_TYPE,
    THEORY_QUESTION_TYPE,
    getCurrentQuestionType,
    getQuestionAttemptKey,
    getQuestionList,
    getQuestionNumber,
} from '../../../helpers/QuestionType'

var gProps;

const isAttempted = (question_id, tabIndex) => {
    var subjectData = gProps.all_exam_subjects_state_data[tabIndex];
    if(!subjectData) return false;
    var attempt = subjectData.attempted_questions[question_id];
    if(!attempt) return false;
    return (attempt.attempt) ? true : false;
}

const getCurrentQuestionIndex = (tabIndex) => {
    let subjectData = gProps.all_exam_subjects_state_data[tabIndex];
    return subjectData.current_question_index;
}

const questionNoSelected = (e) => {
    var question_index = e.target.attributes.getNamedItem('data-question_index').value;
    
    gProps.dispatch({
        type: K.ACTION_QUESTION_NAVIGATED,
        payload: {
            question_index: question_index,
            tab_index: gProps.current_tab,
        }
    }); 
}

const questionTypeSelected = (e) => {
    var question_type = e.target.attributes.getNamedItem('data-question_type').value;

    gProps.dispatch({
        type: K.ACTION_QUESTION_TYPE_CHANGED,
        payload: {
            question_type: question_type,
            tab_index: gProps.current_tab,
        }
    });
}

const question = (props) => {
    
    gProps = props;

    var questionDisplay = Data.exam_data.all_exam_subject_data.map((subject, i) => {

        let questionIndex = getCurrentQuestionIndex(i);
        let subjectStateData = props.all_exam_subjects_state_data[i];
        let questionType = getCurrentQuestionType(subjectStateData);
        let questions = getQuestionList(subject, questionType);
        let objectiveCount = getQuestionList(subject, OBJECTIVE_QUESTION_TYPE).length;
        let theoryCount = getQuestionList(subject, THEORY_QUESTION_TYPE).length;

        var tiles = questions.map((question, index) => {
            let attemptKey = getQuestionAttemptKey(question, questionType);
            return <li data-question_no={getQuestionNumber(question)}
                data-question_id={attemptKey}
                data-question_index={index}
                className={'pointer '+((parseInt(questionIndex) === index)?'current':'')
                    + ' '+ (isAttempted(attemptKey, i)?'attempted':'')}
                key={'tile-' + attemptKey}
                onClick={questionNoSelected}
            >{index + 1}</li>
        });

        return <div className={'tab-pane fade show '+((props.current_tab === i)?'active':'')}
            id={'nav-' + subject.exam_subject_id}
            role="tabpanel" key={'question-' + i} >
            <div className="question-main">
                <div className="question-type-switch mb-3">
                    <button type="button"
                        className={'btn btn-sm mr-2 '+(questionType === OBJECTIVE_QUESTION_TYPE ? 'btn-primary' : 'btn-outline-primary')}
                        data-question_type={OBJECTIVE_QUESTION_TYPE}
                        onClick={questionTypeSelected}>
                        Objective ({objectiveCount})
                    </button>
                    <button type="button"
                        className={'btn btn-sm '+(questionType === THEORY_QUESTION_TYPE ? 'btn-primary' : 'btn-outline-primary')}
                        data-question_type={THEORY_QUESTION_TYPE}
                        onClick={questionTypeSelected}>
                        Theory ({theoryCount})
                    </button>
                </div>
                <SubjectPage questionIndex={questionIndex} subject={subject} tabIndex={i} />
            </div>
            <ul className="question-numbers-tab list-unstyled clearfix text-center">
                {tiles}
            </ul>
        </div>
        
    });

    return (
        <div className="tab-content" id="questions-content" >
            {questionDisplay}
        </div>
    )
}

const mapStateToProps = (state) => {
    return {
        current_tab: state.current_tab,
        all_exam_subjects_state_data: state.all_exam_subjects_state_data,
    }
}
export default connect(mapStateToProps)(question);
