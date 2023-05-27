<?php


?>

<!-- Modal -->
<div class="modal fade" id="courseTopicModal" tabindex="-1" role="dialog"
	aria-labelledby="courseTopicModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="courseTopicModalLabel">Add/Update Topic</h5>
				<button type="button" class="close" data-dismiss="modal"
					aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form action="" method="post" >
        			<div class="form-group">
        				<label for="" >Title</label><br />
        				<input type="text" name="<?= TITLE ?>" value="" class="form-control" placeholder="Topic title" />
						<input type="hidden" name="<?= TABLE_ID ?>" value="" />
        			</div>
        			<div class="form-group">
        				<label for="" >Description</label><br />
        				<textarea name="<?= DESCRIPTION ?>" id="" cols="30" rows="4" class="form-control"
        					placeholder="Topic description" spellcheck="true"></textarea>
        			</div>
        		</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				<button type="button" class="btn btn-primary pointer" id="addUpdateTopicSubmit">Submit</button>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
$(function() {

	var $title = $('#courseTopicModal input[name="{{TITLE}}"]');
	var $description = $('#courseTopicModal textarea[name="{{DESCRIPTION}}"]');
	var $id = $('#courseTopicModal input[name="{{TABLE_ID}}"]');

	$('#courseTopicModal').on('shown.bs.modal', function (e) {
		var id = null;
		if(window.topicToEdit){
			id = topicToEdit['{{TABLE_ID}}'];
			$title.val(topicToEdit['{{TITLE}}']);
			$description.val(topicToEdit['{{DESCRIPTION}}']);
		}
		$id.val(id);
	});
	
	$('#courseTopicModal').on('hidden.bs.modal', function (e) {		
		$title.val('');
		$description.val('');
		$id.val('');
	});
	
	$('#courseTopicModal #addUpdateTopicSubmit').on('click', function (e) {
// 		console.log('submit clicked');
// 		return;
		e.preventDefault();
		showLoading();
		var addr = ($id.val()) 
			? "{{getAddr('ccd_edit_topic')}}/"+$id.val()
			: "{{getAddr('ccd_add_topic')}}/{{$courseId}}";
// 		var title = $title.val();
// 		var description = $description.val();
// 		console.log(addr);
		$.ajax({
		    type: "POST",
		    url: addr,
		    data: $('#courseTopicModal form').serialize(),
		    dataType: "json",
		    success: function(res) {
				console.log(res);
		    	dismissLoading();
			    if(!res.success) return alert(res.message);
			    toastr.success(res.message);
			    if(window.onTopicAddedOrUpdated) onTopicAddedOrUpdated(res.data); 
				$('#courseTopicModal').modal('hide');
		    },
		    error: function(jqHRX, textStatus) {
		    	dismissLoading();
		    	alert("Request failed: " + textStatus);
		    	console.log(jqHRX.responseText);
		    	console.log(textStatus);
		    }
		});
	});
});
</script>