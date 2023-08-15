
class FormatExam{

    questionNo = 0;
    questionIndex;

    subject = { exam_subject_id:'', session_id:'', :'', :'',
        year:'', general_instructions:'', instructions: [], passages: [],
        attempted_questions: [], questions: [this.currentQuestion],
    } 

    currentQuestion = {question_id:'',question_no:'', question:'', 
        option_a:'',option_b:'',option_c:'',option_d:'',option_e:''}

    constructor(subject, questionIndex){
        this.questionIndex = questionIndex;
        this.questionNo = questionIndex + 1;
        this.subject = subject;
        this.currentQuestion = this.subject.questions[this.questionIndex];
    }

    /**@deprecated */
    format() {
        return <div className="question-main">
            <div className="tile text-center p-1 mb-3">
                <div className="tile-title question-no mb-0 shadow py-1">
                    Question {this.questionNo} of {this.subject.questions.length}
                </div>
            </div>
            
            <div className="instruction">{this.formatInstruction()}</div>
            <div className="passage">{this.formatPassage()}</div>
            <div className="question-text" 
                dangerouslySetInnerHTML={{ __html: this.currentQuestion.question }}/>

            <div className="options">
                {this.formatOption('A', this.currentQuestion.option_a)}
                {this.formatOption('B', this.currentQuestion.option_b)}
                {this.formatOption('C', this.currentQuestion.option_c)}
                {this.formatOption('D', this.currentQuestion.option_d)}
                {this.formatOption('E', this.currentQuestion.option_e)}
            </div>
        </div>
    }
    /** @deprecated */
    formatOption(option, optionText) {
        
        if(!optionText) return;

        return <div className="animated-radio-button option">
            <label className="pointer selection"> 
                <span className="option-letter">{option})</span> 
                <input type="radio" name="option" data-selection="{option}" /> 
                <span className="label-text">
                    <span className="option-text"
                        dangerouslySetInnerHTML={{ __html: optionText }}>
                    </span>
                </span>
            </label>
        </div>
    }

    formatInstruction() {}
    formatPassage() {}

    /**@deprecated */
    formatQuestionText() {
        return <div dangerouslySetInnerHTML={{ __html: this.currentQuestion.question }} />
    }
    
}

export default FormatExam;