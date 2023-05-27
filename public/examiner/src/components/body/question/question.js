import Data from '../../../config/startup'
import K from '../../../config/k'
import {connect} from 'react-redux'
import SubjectPage from './subject_page'

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

const question = (props) => {
    
    gProps = props;

    var questionDisplay = Data.exam_data.all_exam_subject_data.map((subject, i) => {

        let questionIndex = getCurrentQuestionIndex(i);

        var tiles = subject.questions.map((question, index) => {
            return <li data-question_no={question.question_no}
                data-question_id={question.question_id}
                data-question_index={index}
                className={'pointer '+((questionIndex == index)?'current':'')
                    + ' '+ (isAttempted(question.question_id, props.current_tab)?'attempted':'')} 
                key={'tile-' + question.question_id}
                onClick={questionNoSelected}
            >{index + 1}</li>
        });

        return <div className={'tab-pane fade show '+((props.current_tab == i)?'active':'')} 
            id={'nav-' + subject.exam_subject_id}
            role="tabpanel" key={'question-' + i} >
            <div className="question-main">
                <SubjectPage questionIndex={questionIndex} subject={subject} />
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
