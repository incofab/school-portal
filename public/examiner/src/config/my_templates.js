
export const attempt_template = {
    tab_index: '',
    exam_subject_id:'',
    question_id: '',
    attempt: ''
}

export const subject_data_template = {
    current_question_index: 0,

    attempted_questions: { //Contains question attempts with key = questiond IDs
        '1':attempt_template,
        '2':attempt_template,
    },
}

export const initialStore = {
    current_tab: 0,
    show_calculator: false,
    /* 
        Content of each subject according to their tab Index.
        So, tabIndex here is the key, i.e 0,1,2,3....
    */
    all_exam_subjects_state_data: [
        JSON.parse(JSON.stringify(subject_data_template)),
        JSON.parse(JSON.stringify(subject_data_template)),
        JSON.parse(JSON.stringify(subject_data_template)),
        JSON.parse(JSON.stringify(subject_data_template)),
        JSON.parse(JSON.stringify(subject_data_template)),
        //More than 5 subjects cannot be written at a time
    ]
}

