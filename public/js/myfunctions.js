function previewImage(imageId, event) {
	var img = document.getElementById(imageId);
	var src = URL.createObjectURL(event.target.files[0]);
	img.src = src;
}

function getImageAddr(courseId, course_session_id, fileName, year) {
	var imgAddr = addr+'exam-img.php'
		+`?course_id=${courseId}&course_session_id=${course_session_id}&filename=${fileName}&session=${year}`;
	//console.log(`imgAddr = ${imgAddr}`);
	return imgAddr;
}


//For filtering tables
//Create a case-insensitive jquery :contains expression
$.expr[':'].icontains = function(a, i, m) {
	return $(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
}
function filter_table(obj, table_id) {
	$('#' + table_id + ' tr').hide(); //First, Hide all rows
	$('#' + table_id + ' tr:first').show(); //Shows the first row which is the header elemet
	$('#' + table_id + ' tr:icontains(' + obj.value.trim() + ')').show();
}

var paginate = {
		
	$allRows : 0,
	numOfRowsPerPage : 10,
	numOfPages : 0,
	currentPage : 1,
	
	init : function(table_id, pageButtonsContainerID) {
		
		paginate.$allRows = $('#' + table_id + ' tbody > tr');
		
		paginate.numOfPages = Math.ceil(paginate.$allRows.length / paginate.numOfRowsPerPage);
		
		paginate.showPageButtons(paginate.numOfPages, pageButtonsContainerID);
		paginate.showPaginatedRows(1);
	},
	showPaginatedRows : function (PageNum) {
		
		paginate.currentPage = PageNum;
		var lastVisibleRow = paginate.currentPage * paginate.numOfRowsPerPage;
		var i = 1;
		paginate.$allRows.each(function() {
			if(i <= lastVisibleRow &&  i > lastVisibleRow - paginate.numOfRowsPerPage){
				$(this).show();
			}else{
				$(this).hide();
			}	
			i++;
		});
		
		if(paginate.currentPage == 1){
			$('#paginate_previous').hide();
		}else{
			$('#paginate_previous').show();
		}
		if(paginate.currentPage == paginate.numOfPages){
			$('#paginate_next').hide();
		}else{
			$('#paginate_next').show();
		}
		//Highlight active nav button
		i = 0; //Reset i
		$('ul#paginate li a').each(function() {
			if(paginate.currentPage == i){
				$(this).addClass('current_page');
			}else{
				$(this).removeClass('current_page');
			}
			i++;
		});
	},
	showPageButtons : function (numOfPages, pageButtonsContainerID) {
		
		if(numOfPages < 2){
			$('#' + pageButtonsContainerID).html('');
			return;
		}
		
//		var str = '<a href="javascript:paginate.previousPage()" id="paginate_previous" >Previous</a>';
		var str = '';
		
		str += '<ul id="paginate" class="pagination">\n';
		str += '<li><a href="javascript:paginate.previousPage()" id="paginate_previous" >&laquo;</a></li>';
		for (var i = 0; i < numOfPages; i++) {
			str += '<li><a href="javascript:paginate.showPaginatedRows(' + (i+1) + ')" >' + (i+1) + '</a></li>\n';
		}
		
		str += '<li><a href="javascript:paginate.nextPage()" id="paginate_next">&raquo;</a></li>';
		str += '</ul>';
		
//		str += '<a href="javascript:paginate.nextPage()" id="paginate_next" >Next</a>'

		$('#' + pageButtonsContainerID).html(str);
	},
	
	nextPage : function() {
		//First of all, Increase the current page number
		paginate.currentPage++;
		//Then check if it exceeded the upper and lower limits
		if(paginate.currentPage > paginate.numOfPages)
			paginate.currentPage = paginate.numOfPages;
		if(paginate.currentPage < 1)
			paginate.currentPage = 1;
		
		paginate.showPaginatedRows(paginate.currentPage);
		
	},
	previousPage : function() {
		//First of all, Increase the current page number
		paginate.currentPage--;
		//Then check if it exceeded the upper and lower limits
		if(paginate.currentPage > paginate.numOfPages)
			paginate.currentPage = paginate.numOfPages;
		if(paginate.currentPage < 1)
			paginate.currentPage = 1;
		
		paginate.showPaginatedRows(paginate.currentPage);
		
	},
	reArrangePage : function(obj, table_id, pageButtonsContainerID) {
		paginate.numOfRowsPerPage = $(obj).val();
		paginate.init(table_id, pageButtonsContainerID);
	}
	
}

//var numOfRowsPerPage = 10;
//var numOfPages = Math.ceil(total / numOfRowsPerPage);















/**
 * Generic function for students, parents, teachers and admin registration
 * @returns {Boolean}
 */
function val_regform() {
	var $names = $('.names'); 
	var $username = $('.username'); 
	var $password = $('.password'); 
	var $cpassword = $('.password_confirmation');
	var $phone = $('.phone');
	var $email = $('.email'); //Email is validated only if it is a compulsory/required field
	var $required = $('.required');
	var $select = $('.select'); //for class, terms, subjects, terms, sessions 
	
	if ($names.length > 0){
		var names_error_count = 0;
		$names.each(function() {
			$(this).css('border', '');
		    if (!val_names($(this), 'Name must be at least 3 characters long')){
		    	names_error_count++;
		    	return false;
		    }
		});
		if(names_error_count > 0) return false;
	}
	if($username.length > 0){
		if(!val_username($username))
			return false;
	}
	if($password.length > 0){
		if(!val_password($password, $cpassword))
			return false;
	}
	if($phone.length > 0){
		if(!val_phone($phone))
			return false;
	}
	if($email.length > 0){
		if(!val_email($email))
			return false;
	}	
	if($required.length > 0){
		var required_error_count = 0;
		$required.each(function() {
			$(this).css('border', '');
			if(isempty($(this).val())){
				$(this).css('border', 'solid 1px red');
				$(this).focus();
				alert('The highlighted field is required');
				required_error_count++;
				return false;
			}
		});
		if(required_error_count > 0) return false;
	}
	if ($select.length > 0){
		var select_error_count = 0;
		$select.each(function() {
			$(this).css('border', '');
		    if (!val_select($(this))){
		    	select_error_count++;
		    	return false;
		    }
		});
		if(select_error_count > 0) return false;
	}
	return true;
}
function isempty(val) {
	try {
		if (val == null || val == '' || val.length < 1)
			return true;
	} catch (e) {
		return true;
	}
	return false;
}
function val_username($username) {
	if($username == null) return false;
	$username.css('border', '');
	//Must be at least 6 characters long
	if ($username.val().length < 4){
        alert('Username must be at least 4 characters long');
        $username.focus();
        $username.css('border', 'solid 1px red');
        return false;
    }
	var regexx = /^[A-Za-z0-9_]+$/;
	 //only alpha numeric characters, digits and underscores are allowed... no spaces, no special characters
    if (!regexx.test($username.value)){
     	alert("Only alphanumerals, digits and underscores are allowed in username field" +
     			"No spaces or special characters" );
        $username.focus();
        $username.css('border', 'solid 1px red');
       	return false;
    } 
	return true;
}
function val_password($password, $cpassword) { 
	if($password == null || $cpassword == null) return false;
	$password.css('border', '');
	$cpassword.css('border', '');
    // Check that the password is sufficiently long (min 6 chars)
    // The check is duplicated below, but this is included to give more
    // specific guidance to the user
    if ($password.val().length < 4) {
        alert('Passwords must be at least 4 characters long.');
        $password.focus();
        $password.css('border', 'solid 1px red');
        return false;
    }
    // Check password and confirmation are the same
    if ($password.val() != $cpassword.val()) {
        alert('Password entered MUST match the value entered in Confirm Password');
        $password.focus();
        $password.css('border', 'solid 1px red');
        return false;
    }
    return true;
}
function val_names($name, msg) {
	try {	
		var regexx = /^[ A-Za-z_]+$/;
		 //Must be at least 6 characters long
		if ($name.val().length < 3){
	        alert(msg);
	        $name.css('border', 'solid 1px red');
	        $name.focus();
	        return false;
	    }
	    //only alpha numeric characters, digits and underscores are allowed... no spaces, no special characters
	    if (!regexx.test($name.val())){
	     	alert('Only alphanumerals, spaces and underscores are allowed in this field, No special characters');
	        $name.focus();
	        $name.css('border', 'solid 1px red');
	       	return false;
	    } 
	} catch (e) { return false; }
    return true;
}
/**
 * Use the validate select element. Checks if the user tried to submit without making a selection, 
 * leaving it at the default 'Select $' value
 * @param $select
 * @returns {Boolean}
 */ 
function val_select($select) {
	if ($select == null) return false;
	var s = $select.val();
    if (s.length < 1 || (s.substring(0, 6)).toLowerCase() == 'select'){
     	alert('Make a selection');
     	$select.focus();
     	$select.css('border', 'solid 1px red');
      	return false;
    }
	return true;
}
function val_email($email) {
	if($email == null) return false;
	$email.css('border', '');
    //matching for email. must be in this format name@example.com
    var regexx = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,6})+$/;
    if (!regexx.test($email.val())){
      	alert('Enter a valid E-mail address');
       	$email.focus(); 
       	$email.css('border', 'solid 1px red');
       	return false;
    }
    return true; 
}
function val_phone($phone) {
	if($phone == null) return false;
	$phone.css('border', '');
   var regexx = /^[0-9]+$/;
    if (!regexx.test($phone.val())){
     	alert('Enter a valid Phone number, only digits are allowed');
      	$phone.focus();
      	$phone.css('border', 'solid 1px red');
      	return false;
    }
    if ($phone.val().length < 7){
     	alert('Phone number must be up to 7 digits');
      	$phone.focus();
      	$phone.css('border', 'solid 1px red');
      	return false;
    }
    return true;
}