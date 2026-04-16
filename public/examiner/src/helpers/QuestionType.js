export const OBJECTIVE_QUESTION_TYPE = 'objective';
export const THEORY_QUESTION_TYPE = 'theory';

export function getCurrentQuestionType(subjectStateData) {
    return subjectStateData && subjectStateData.current_question_type
        ? subjectStateData.current_question_type
        : OBJECTIVE_QUESTION_TYPE;
}

export function getQuestionList(subject, questionType) {
    if (questionType === THEORY_QUESTION_TYPE) {
        return subject.theory_questions || [];
    }
    return subject.questions || [];
}

export function getQuestionId(question) {
    return question.question_id || question.id;
}

export function getQuestionAttemptKey(question, questionType) {
    const questionId = getQuestionId(question);
    return questionType === THEORY_QUESTION_TYPE
        ? 'theory-' + questionId
        : questionId;
}

export function getQuestionNumber(question) {
    return question.question_no;
}
