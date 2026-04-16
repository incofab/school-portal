import FormatExam from '../../../helpers/FormatExam'
import Option from '../option/option'
import K from '../../../config/k'
import {connect} from 'react-redux'
import {
    OBJECTIVE_QUESTION_TYPE,
    THEORY_QUESTION_TYPE,
    getCurrentQuestionType,
    getQuestionAttemptKey,
    getQuestionId,
    getQuestionList,
} from '../../../helpers/QuestionType'

import React, { Component } from 'react'

export class subjectPage extends Component {

    render() {
        
        var subject = this.props.subject;
        var questionIndex = this.props.questionIndex;
        var tabIndex = this.props.tabIndex;
        var subjectStateData = this.props.all_exam_subjects_state_data[tabIndex];
        var questionType = getCurrentQuestionType(subjectStateData);
        var questions = getQuestionList(subject, questionType);
    
        var questionNo = parseInt(questionIndex) + 1;
        var currentQuestion = questions[questionIndex];

        if(!currentQuestion) {
            return <div className="question-main">
                <div className="tile text-center p-1 mb-3">
                    <div className="tile-title question-no mb-0 shadow py-1">
                        No {questionType} questions for this subject.
                    </div>
                </div>
            </div>
        }
    
        let examFormater = new FormatExam(subject, questionIndex, questionType);
        let attemptKey = getQuestionAttemptKey(currentQuestion, questionType);
        let theoryAttempt = subjectStateData.attempted_questions[attemptKey];
    
        return (
            <div className="question-main">
                <div className="tile text-center p-1 mb-3">
                    <div className="tile-title question-no mb-0 shadow py-1">
                        Question {questionNo} of {questions.length}
                        {questionType === THEORY_QUESTION_TYPE ? ` (${currentQuestion.marks} marks)` : ''}
                    </div>
                </div>
                
                <div className="instruction">{examFormater.formatInstruction()}</div>
                <div className="passage">{examFormater.formatPassage()}</div>
                <div className="question-text" 
                    dangerouslySetInnerHTML={{ __html: K.handleExamImgs(currentQuestion.question, subject) }}/>
    
                {questionType === OBJECTIVE_QUESTION_TYPE ? <div className="options">
                    
                    <Option option={'A'} 
                        subject={subject} 
                        exam_subject_id={subject.exam_subject_id} 
                        question_id={getQuestionId(examFormater.currentQuestion)}
                        optionText={examFormater.currentQuestion.option_a} />
                    <Option option={'B'} 
                        subject={subject} 
                        exam_subject_id={subject.exam_subject_id} 
                        question_id={getQuestionId(examFormater.currentQuestion)}
                        optionText={examFormater.currentQuestion.option_b} />
                    <Option option={'C'} 
                        subject={subject} 
                        exam_subject_id={subject.exam_subject_id} 
                        question_id={getQuestionId(examFormater.currentQuestion)}
                        optionText={examFormater.currentQuestion.option_c} />
                    <Option option={'D'} 
                        subject={subject} 
                        exam_subject_id={subject.exam_subject_id} 
                        question_id={getQuestionId(examFormater.currentQuestion)}
                        optionText={examFormater.currentQuestion.option_d} />
                    <Option option={'E'} 
                        subject={subject} 
                        exam_subject_id={subject.exam_subject_id} 
                        question_id={getQuestionId(examFormater.currentQuestion)}
                        optionText={examFormater.currentQuestion.option_e} />
                    
                </div> : <textarea
                    className="form-control theory-answer"
                    value={theoryAttempt ? theoryAttempt.attempt : ''}
                    placeholder="Type your answer here"
                    onChange={(e) => this.props.dispatch({
                        type: K.ACTION_ANSWER_SELECTED,
                        payload: {
                            'tab_index':this.props.current_tab,
                            'exam_subject_id':subject.exam_subject_id,
                            'question_id':attemptKey,
                            'attempt':e.target.value,
                            'question_type':THEORY_QUESTION_TYPE,
                        }
                    })}
                />}
            </div>
        );
    }

}

const mapStateToProps = (state) => {
    return {
        current_tab: state.current_tab,
        all_exam_subjects_state_data: state.all_exam_subjects_state_data,
    }
}

export default connect(mapStateToProps)(subjectPage);

