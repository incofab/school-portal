<?php

?>

<div class="clearfix">
    <!-- Button trigger modal -->
    <button type="button" class="btn btn-primary pull-right ml-3" id="openAddTopicModal"
    	>Add Topic</button>
<!--     	data-target="#courseTopicModal" -->
    
    <div class="dropdown pull-left" id="select-topic-dropdown">
    	<button class="btn btn-secondary dropdown-toggle" type="button"
    		id="dropdownMenu2" data-toggle="dropdown" aria-haspopup="true"
    		aria-expanded="false">Select Topic</button>
    	<div class="dropdown-menu" aria-labelledby="dropdownMenu2" id="" >
    <!-- 		<button class="dropdown-item" type="button">Something else here</button> -->
    	</div>
    </div>
</div>

@include('ccd.courses._add_topic')

<script type="text/javascript" >

var topicsArr = <?= json_encode($allTopics->toArray()); ?>;

var selectedTopicTitle = '<?= $topic[TITLE]; ?>';

var topicToEdit = null;

if(!selectedTopicTitle || selectedTopicTitle.length < 1) selectedTopicTitle = 'Select Topic';

function updateDropDown() {

    $('#select-topic-dropdown .dropdown-toggle').text(selectedTopicTitle);
    
	var options = '<div class="dropdown-item select-topic">Select Topic</div>';

    for (var i = 0; i < topicsArr.length; i++) {

        var topic = topicsArr[i];

        var id = topic['{{TABLE_ID}}'];

        var title = topic['{{TITLE}}'];
        
    	options += '<div class="dropdown-divider"></div>'
        	+'<div class="py-2 dropdown-item is-topic '+((selectedTopicTitle == title) ? 'active' : '')+'" data-id="'+id+'" data-title="'+title+'" >'
    		+ '<div class="">'
    			+'<span class="d-inline-block cursor-default">'+title+'</span> '
    			+'<i class="fa fa-pencil ml-2 cursor-pointer text-success pointer edit" data-index="'+i+'"'
    				+'></i>'
    			+'<i class="fa fa-trash cursor-pointer ml-2 text-danger pointer delete" data-index="'+i+'" '
    				+'></i>'
    		+'</div>'
    	+'</div>';
    }	

    $('#select-topic-dropdown .dropdown-menu').html(options);
}

function resetDropDown() {

	selectedTopicTitle = 'Select Topic';

	updateDropDown();
}

$('#select-topic-dropdown .dropdown-menu').on('click', '.dropdown-item.select-topic', function(e) {
	
	resetDropDown();
	
});

$('#select-topic-dropdown .dropdown-menu').on('click', '.dropdown-item.is-topic', function(e) {

	selectedTopicTitle = $(this).data('title');

	$('form[name="record-question"] input[name="{{TOPIC_ID}}"]').val($(this).data('id'));
	$('form[name="record-question"] input[name="topic_title"]').val(selectedTopicTitle);

	updateDropDown();
});

$('#openAddTopicModal').on('click', function(e) {

	topicToEdit = null;
	
	$('#courseTopicModal').modal('show');
});

$('#select-topic-dropdown .dropdown-menu').on('click', '.dropdown-item.is-topic .edit', function(e) {

	e.stopPropagation();
	
	var index = $(this).data('index');

	var topic = topicsArr[index];

	topicToEdit = topic;
	
	$('#courseTopicModal').modal('show');

	return true;
});

$('#select-topic-dropdown .dropdown-menu').on('click', '.dropdown-item.is-topic .delete', function(e) {

	e.stopPropagation();
	
	var index = $(this).data('index');

	var topic = topicsArr[index];

	if(!confirm('Delete '+topic.title+'?')) return true;
// 	if(!confirm('Delete '+topic.title+'?\nThis will delete any question already attached to this topic')) return true;
	
	deleteTopic(topic['{{TABLE_ID}}']);

	return true;
});

function onTopicAddedOrUpdated(topicData) {

	var index = getTopicIndex(topicData['{{TABLE_ID}}']);

	var prevTopic = topicsArr[index];

	if(prevTopic && prevTopic.title == selectedTopicTitle){
		selectedTopicTitle = topicData.title;
		$('form[name="record-question"] input[name="topic_title"]').val(selectedTopicTitle);
	}
	
	if(index == -1) topicsArr.push(topicData);

	else topicsArr[index] = topicData;

	updateDropDown();
}

function onTopicDeleted(topicId) {
	
	var index = getTopicIndex(topicId);

	var theTopic = topicsArr[index];

	topicsArr.splice(index, 1);

	if(selectedTopicTitle == theTopic.title){
		
		$('form[name="record-question"] input[name="topic_title"]').val('');
		
		$('form[name="record-question"] input[name="{{TOPIC_ID}}"]').val('');

		resetDropDown();

	}else{
		
		updateDropDown();
		
	}
}

function getTopicIndex(topicId) {

    for (var i = 0; i < topicsArr.length; i++) {

        var topic = topicsArr[i];

        var id = topic['{{TABLE_ID}}'];

        if(id == topicId) return i;
    }

    return -1;
}

function deleteTopic(topicId) {
	$.ajax({
	    type: "GET",
	    url: "{{getAddr('ccd_delete_topic')}}/"+topicId,
// 	    data: $(this).serialize(),
	    dataType: "json",
	    success: function(res) {
	    	dismissLoading();
		    if(!res.success) return alert(res.message);
		    toastr.success(res.message);
		    if(window.onTopicDeleted) onTopicDeleted(topicId); 
	    },
	    error: function(jqHRX, textStatus) {
	    	dismissLoading();
	    	alert("Request failed: " + textStatus);
	    	console.log(jqHRX.responseText);
	        console.log(textStatus);
	    }
	});
}

$(function() {

	updateDropDown();

});

</script>

