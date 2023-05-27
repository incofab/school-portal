import FormatExam from '../../../helpers/FormatExam'
import Option from '../option/option'
import K from '../../../config/k'
import {connect} from 'react-redux'

import React, { Component } from 'react'

export class subjectPage extends Component {

    render() {
        
        var subject = this.props.subject;
        var questionIndex = this.props.questionIndex;
    
        var questionNo = parseInt(questionIndex) + 1;
        var currentQuestion = subject.questions[questionIndex];
    
        let examFormater = new FormatExam(subject, questionIndex);
    
        return (
            <div className="question-main">
                <div className="tile text-center p-1 mb-3">
                    <div className="tile-title question-no mb-0 shadow py-1">
                        Question {questionNo} of {subject.questions.length}
                    </div>
                </div>
                
                <div className="instruction">{examFormater.formatInstruction()}</div>
                <div className="passage">{examFormater.formatPassage()}</div>
                <div className="question-text" 
                    dangerouslySetInnerHTML={{ __html: K.handleExamImgs(currentQuestion.question, subject) }}/>
    
                <div className="options">
                    
                    <Option option={'A'} 
                        subject={subject} 
                        exam_subject_id={subject.exam_subject_id} 
                        question_id={examFormater.currentQuestion.question_id}
                        optionText={examFormater.currentQuestion.option_a} />
                    <Option option={'B'} 
                        subject={subject} 
                        exam_subject_id={subject.exam_subject_id} 
                        question_id={examFormater.currentQuestion.question_id}
                        optionText={examFormater.currentQuestion.option_b} />
                    <Option option={'C'} 
                        subject={subject} 
                        exam_subject_id={subject.exam_subject_id} 
                        question_id={examFormater.currentQuestion.question_id}
                        optionText={examFormater.currentQuestion.option_c} />
                    <Option option={'D'} 
                        subject={subject} 
                        exam_subject_id={subject.exam_subject_id} 
                        question_id={examFormater.currentQuestion.question_id}
                        optionText={examFormater.currentQuestion.option_d} />
                    <Option option={'E'} 
                        subject={subject} 
                        exam_subject_id={subject.exam_subject_id} 
                        question_id={examFormater.currentQuestion.question_id}
                        optionText={examFormater.currentQuestion.option_e} />
                    
                </div>
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


