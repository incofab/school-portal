
var data = window.mainContent;

const demoData = {
	"exam_data": {
		"all_exam_subject_data": [
			{
				"exam_subject_id": 1,
				"session_id": 16,
				"": "ENG JSS",
				"": "English JSS",
				"year": "2019",
                'course_id': 4,
				"general_instructions": "Answer all questions",
				"instructions": [],
				"passages": [],
				"attempted_questions": {
					'421': {'question_id': 421, 'attempt': 'B'},
					'422': {'question_id': 422, 'attempt': 'C'} 
				}
				,
				"questions": [
					{
						"question_id": 421,
						"question_no": 1,
						"question": "<p>The students listen ________ to their teacher.<\/p>",
						"option_a": "<p>patiently<\/p>",
						"option_b": "<p>elegantly<\/p>",
						"option_c": "<p>attentively<\/p>",
						"option_d": "<p>focused<\/p>",
						"option_e": ""
					},
					{
						"question_id": 422,
						"question_no": 2,
						"question": "<p>The crops failed _______ the rain were quite good.<\/p>",
						"option_a": "<p>since<\/p>",
						"option_b": "<p>because<\/p>",
						"option_c": "<p>although<\/p>",
						"option_d": "<p>however<\/p>",
						"option_e": ""
					},
					{
						"question_id": 423,
						"question_no": 3,
						"question": "<p>The teacher was _________ about the story Ola told her<\/p>",
						"option_a": "<p>looking<\/p>",
						"option_b": "<p>seeing<\/p>",
						"option_c": "<p>skeptical<\/p>",
						"option_d": "<p>painstakingly<\/p>",
						"option_e": ""
					},
					{
						"question_id": 424,
						"question_no": 4,
						"question": "<p>The managing director did not pay his staff last month _________ ?<\/p>",
						"option_a": "<p>didn't he<\/p>",
						"option_b": "<p>had he not<\/p>",
						"option_c": "<p>is not it<\/p>",
						"option_d": "<p>did he<\/p>",
						"option_e": ""
					},
					{
						"question_id": 425,
						"question_no": 5,
						"question": "<p>She doesn't have to attend the lecture ________ ?<\/p>",
						"option_a": "<p>doesn't she<\/p>",
						"option_b": "<p>is she<\/p>",
						"option_c": "<p>does she&nbsp;<\/p>",
						"option_d": "<p>has she<\/p>",
						"option_e": ""
					}
				]
			},
			{
				"exam_subject_id": 2,
				"session_id": 2,
				"": "ENG SSS",
				"": "English SSS",
				"year": "2019",
				"general_instructions": "Answer all questions",
				"instructions": [],
				"passages": [],
				"attempted_questions": [],
				"questions": [
					{
						"question_id": 31,
						"question_no": 1,
						"question": "<p>The car belongs to Mrs Smith. it is _____________<\/p>",
						"option_a": "<p>hers<\/p>",
						"option_b": "<p>her's<\/p>",
						"option_c": "<p>hers'<\/p>",
						"option_d": "<p>her<\/p>",
						"option_e": ""
					},
					{
						"question_id": 32,
						"question_no": 2,
						"question": "<p>These boxes are bigger than __________ over there.<\/p>",
						"option_a": "<p>this<\/p>",
						"option_b": "<p>them<\/p>",
						"option_c": "<p>those<\/p>",
						"option_d": "<p>these<\/p>",
						"option_e": ""
					},
					{
						"question_id": 33,
						"question_no": 3,
						"question": "<p>This is __________ than that.<\/p>",
						"option_a": "<p>heavier<\/p>",
						"option_b": "<p>more heavy<\/p>",
						"option_c": "<p>most heavy<\/p>",
						"option_d": "<p>heaviest<\/p>",
						"option_e": ""
					},
					{
						"question_id": 34,
						"question_no": 4,
						"question": "<p>Of the three girls, Tola is the _________&nbsp;<\/p>",
						"option_a": "<p>beautiful<\/p>",
						"option_b": "<p>most beautiful<\/p>",
						"option_c": "<p>beautifullest<\/p>",
						"option_d": "<p>more beautiful<\/p>",
						"option_e": ""
					},
					{
						"question_id": 35,
						"question_no": 5,
						"question": "<p>It is Kunle who ________ done this<\/p>",
						"option_a": "<p>have<\/p>",
						"option_b": "<p>had&nbsp;<\/p>",
						"option_c": "<p>having<\/p>",
						"option_d": "<p>has<\/p>",
						"option_e": ""
					}
				]
			}
		],
		"meta": {
			"time_remaining": 2700
		}
	},
	/*
	"student_data": { //Student_data not useed
		"firstname": "Ciroma",
		"lastname": "Chukwuma Adekunle",
		"user_id": "11111111",
		"exam_no": "00000000"
	},
	*/
	"data": {
		"firstname": "Ciroma",
		"lastname": "Chukwuma Adekunle",
		"user_id": "",
		"exam_no": "00000000",
		"token": "ewmedijemiw943ne89232jdmsadnci23"
	}
}

if(!data) data = demoData;
//console.log(data);
export default data;