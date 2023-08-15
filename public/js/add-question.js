
/***** Init ****/
const DB_NAME = 'ecd';
const QUESTIONS_TABLE = 'questions';
const PING_INTERVAL = 30*1000; // 30 seconds
var db;
if(window.openDatabase){
	db = openDatabase(DB_NAME,"1.0","My_ECD_database",65535);
	createTable();
}
ping();
/***** // Init ****/


function ping() {
	setInterval(() => {
		getQuestion(sendData);
	}, PING_INTERVAL);
	
}
$('form[name="record-question"]').on('submit', function(e) {
	e.preventDefault();
	if(!confirm('Are you sure?')) return false;
	tinyMCE.triggerSave();
	
	sendData($(this).serialize(), null, currentQuestionNo);
	
	currentQuestionNo++;
	clearForm(currentQuestionNo);
	$('body, html').animate({scrollTop: 0}, 800);

	return false;
});

function sendData(data, id, questionNo) {
	$.ajax({
		type: "POST",
		url: window.addQuestionAPI,
		data: data, //$(this).serialize(),
		dataType: "json",
		success: function(res) {
			if(!res.success && !res.is_duplicate){
				uploadFailed(data, id, questionNo);
				console.log('sendData() Error: '+res.message);
				return; 
			}
			uploadSuccessful(data, id, questionNo);
		},
		error: function(jqHRX, textStatus) {
			uploadFailed(data, id, questionNo);
			console.log('sendData() Error:', textStatus, jqHRX);
		}
	});
}

function uploadFailed(data, id, questionNo) {	
	saveQuestion(data, id, questionNo);
}

function uploadSuccessful(data, id, questionNo) {	
	if(id)deleteQuestion(id);
}

function clearForm(currentQuestionNo) {
	tinyMCE.get().forEach(function(editor){
		editor.setContent('');
	});
	$('form[name="record-question"] .form-control').val('');
	$('form[name="record-question"] input[name="question_no"]').val(currentQuestionNo).trigger('change');
	if(window.resetDropDown) resetDropDown();
}

function createTable() 
{
	if(!db) return;
	db.transaction(function(transaction){
		var sql=`CREATE TABLE IF NOT EXISTS ${QUESTIONS_TABLE} (
			id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
			course_session_id INT(5) NOT NULL,
			question_no INT(5) NOT NULL,
			content TEXT NOT NULL
		)`;
		transaction.executeSql(sql,undefined,function(){
			console.log("Table is created successfully");
		},function(){
			console.log("Table is already being created");
		})
	});
}

function saveQuestion(data, id, questionNo) 
{
	// If it has an ID, then, it's coming from DB and therefore already recorded.
	if(id) return;

	if(!db) return;
	db.transaction(function (transaction) {

		var sql = `INSERT INTO ${QUESTIONS_TABLE}(course_session_id,question_no,content) VALUES(?,?,?)`;
		var dataStr = JSON.stringify(data);
		transaction.executeSql(sql, [window.courseSessionId, questionNo, dataStr], function () {
			console.log(`New item is added successfully, Question_no = ${questionNo}`);
		}, function (transaction, err) {
			alert('Data not saved: '+err.message);
		});
	});
}

function getQuestion(fnSendData) 
{
	if(!db) return;
	db.transaction(function(transaction){
		
		var sql=`SELECT * FROM ${QUESTIONS_TABLE} ORDER BY id ASC`;

		transaction.executeSql(sql,undefined,function(transaction, result){
			
			if(!result.rows.length){
				// console.log('Table empty');
				return;
			}
			// console.log('No of items: '+result.rows.length);

			var row = result.rows.item(0);

			var id = row.id;
			var course_session_id = row.course_session_id;
			var question_no = row.question_no;
			var content = JSON.parse(row.content);

			// Work with the data
			if(fnSendData) fnSendData(content, id, question_no);

		}, function(transaction, err){
			console.log('Database error: '+err.message);
		})
	})

}

function deleteQuestion(id) 
{
	if(!db) return;
	db.transaction(function(transaction){
		
		var sql=`DELETE FROM ${QUESTIONS_TABLE} WHERE id=?`;

		transaction.executeSql(sql,[id],function(){
			console.log("Table row is deleted successfully")
		},function(transaction,err){
			alert(err.message);
		});

	});
}

function truncateTable() {
	if(!db) return;
	db.transaction(function(transaction){
		var sql= `DROP TABLE ${QUESTIONS_TABLE}`;
		transaction.executeSql(sql,undefined,function(){
			createTable();
			console.log("Table is trucated successfully");
		},function(transaction,err){
			console.log(err.message);
		})
	});
}



