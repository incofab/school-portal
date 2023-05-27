import K from '../config/k'


export const TAB_CHANGED = {
    "type": K.ACTION_TAB_CHANGED,
    "payload": {
        'selected_tab': 0,
    },
}

export const ANSWER_SELECTED = {
    "type": K.ACTION_ANSWER_SELECTED,
    "payload": {
        'exam_subject_id':'',
        'question_id':'',
        'attempt':'',
        'tab_index': 0
    },
}

export const QUESTION_NAVIGATED = {
    "type": K.ACTION_QUESTION_NAVIGATED,
    "payload": {
        'question_index': 0,
        'tab_index': 0
    },
}

export const ANSWERS_UPLOADED = {
    "type": K.ACTION_ANSWERS_UPLOADED,
    "payload": {
    },
}

