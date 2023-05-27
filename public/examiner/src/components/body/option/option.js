import K from '../../../config/k'
import {connect} from 'react-redux'

import React, { Component } from 'react'

export class OptionComponent extends Component {

    optionText;
    option;
    question_id;
    exam_subject_id;
    subject_state_data;

    constructor(props){
        super(props);
    }
    
    answerSelected = () => {
        this.props.dispatch({
            type: K.ACTION_ANSWER_SELECTED,
            payload: {
                'tab_index':this.props.current_tab,
                'exam_subject_id':this.exam_subject_id,
                'question_id':this.question_id,
                'attempt':this.option,
            }
        });
    }

    checkChanged = () => {}

    getAttempt = (question_id) => {
        var attempt = this.subject_state_data.attempted_questions[question_id];
        if(!attempt) return;
        return attempt.attempt;
    }

    render() {
        this.optionText = this.props.optionText;
        this.option = this.props.option;
    
        this.question_id = this.props.question_id;
        this.exam_subject_id = this.props.exam_subject_id;
        this.subject_state_data = this.props.all_exam_subjects_state_data[this.props.current_tab];
        
        if(!this.optionText) return <></>;

        return (
            <div className="animated-radio-button option pointer" onClick={this.answerSelected}>
                <label className="selection"> 
                    <span className="option-letter">{this.option})</span> 
                    <input type="radio" name="option" 
                        checked={((this.getAttempt(this.question_id)==this.option)?'checked':'')}
                        data-selection={this.option} onChange={this.checkChanged} /> 
                    <span className="label-text">
                        <span className="option-text"
                            dangerouslySetInnerHTML={{ __html: K.handleExamImgs(this.optionText, this.props.subject) }}>
                        </span>
                    </span>
                </label>
            </div>
        )
    }
}

const mapStateToProps = (state) => {
    return {
        current_tab: state.current_tab,
        all_exam_subjects_state_data: state.all_exam_subjects_state_data,
    }
}
// export default option
export default connect(mapStateToProps)(OptionComponent);
