import K from '../config/k'
import * as Templates from '../config/my_templates'
import Data from '../config/startup'
import {produce} from 'immer'
import examHandler from '../helpers/ExamHandler'
import examSync from '../helpers/ExamSync'

const getInitializedStore = () => {
    
    // Data.exam_data.all_exam_subject_data.map((subjectData, i) => {
    Data.exam_data.all_exam_subject_data.forEach((subjectData, i) => {
        Templates.initialStore.all_exam_subjects_state_data[i].attempted_questions = subjectData.attempted_questions;
    });

    return Templates.initialStore;
}

var iStore = getInitializedStore();

function reducer(state = iStore, action) {
    var p = action.payload;
    switch (action.type) {
        case K.ACTION_TAB_CHANGED:
            let atcNewState = {
                ...state,
                current_tab: action.payload.selected_tab
            }
            
            return atcNewState;

            case K.ACTION_ANSWERS_UPLOADED:
                break;

            case K.ACTION_ANSWER_SELECTED:
                // console.log('Answer Payload', p);
                let newState = produce(state, (draftState) => {
                    let subjectData = draftState.all_exam_subjects_state_data[p.tab_index];
                    
                    if(!subjectData) return;
                    
                    let attempt = subjectData.attempted_questions[p.question_id];
                    
                    if(!attempt) attempt = {};

                    // Check if user selected the exact same option
                    if(attempt.attempt === p.attempt) return;
                    
                    attempt.exam_subject_id = p.exam_subject_id;
                    attempt.question_id = p.question_id;
                    attempt.attempt = p.attempt;
                    
                    subjectData.attempted_questions[p.question_id] = attempt;
                    
                    examHandler.addToAttemptedQuestions(p.attempt);
                    examSync.addAttempt(p);
                });
                
                return newState;
            
        case K.ACTION_QUESTION_NAVIGATED:
            return produce(state, (draftState) => {
                let subjectData = draftState.all_exam_subjects_state_data[p.tab_index];
                
                if(!subjectData) return;
                
                subjectData.current_question_index = p.question_index;
            });

        case K.ACTION_TOGGLE_CALCULATOR:
            return produce(state, (draftState) => {
                draftState.show_calculator = p.show_calculator;
            });
    
        default:
            return state;
            
    }

    return state;
}

export default reducer;

