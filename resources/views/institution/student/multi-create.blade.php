<?php
$title = "Add Student - Institution | " . SITE_TITLE;
?>

@extends('institution.layout')

@section('dashboard_content')

<div class="app-title">
	<div>
		<h1>
			<i class="fa fa-dashboard"></i> Students
		</h1>
		<p>Register a student in this institution</p>
	</div>
	<ul class="app-breadcrumb breadcrumb">
		<li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i> <a href="{{route('institution.dashboard', $institution->id)}}">Dashboard</a></li>
		<li class="breadcrumb-item"><a href="{{route('institution.student.index', $institution->id)}}">Students</a></li>
		<li class="breadcrumb-item">Register</li>
	</ul>
</div>
@include('common.message')
<div>
	<div class="tile">
		<h3 class="tile-title">Register Multiple Student</h3>
		<form action="{{route('institution.student.multi-store', $institution->id)}}" method="post">
    		@csrf
    		<div class="tile-body" id="student-container">
    			<div class="student-rows" id="row-1">
					<div class="row border p-2 p-md-3">
						<div class="col">
							<div class="form-group">
								<label class="control-label">Firstname</label> 
								<input type="text" name="students[1][firstname]" value="" 
									required="required" placeholder="Firstname" class="form-control firstname" >
							</div>
						</div>
						<div class="col">
							<div class="form-group">
								<label class="control-label">Lastname</label> 
								<input type="text" name="students[1][lastname]" value="" 
									required="required" placeholder="Lastname" class="form-control lastname" >
							</div>
						</div>
						<div class="col">
							<div class="form-group">
								<label class="control-label">Class [optional]</label>
								<select name="students[1][grade_id]" class="form-control grade_id">
									<option value="">Select Class</option>
									@foreach($allGrades as $grade)
										<option value="{{$grade->id}}"
										title="{{$grade->description}}" >{{$grade->title}}</option>
									@endforeach
								</select>
							</div>
						</div>
						<div class="col-auto">
							<button type="button" role="button" class="mt-3" 
								data-id="1" id="remove-row">
								<i class="fa fa-times text-danger"></i>
							</button>
						</div>
					</div>
				</div>
    		</div>
			<div class="clearfix mt-3">
				<button type="button" role="button" id="add-row"
				class="btn btn-warning float-right">
					<i class="fa fa-plus"></i> Add
				</button>
			</div>
    		<div class="tile-footer">
    			<button class="btn btn-primary" type="submit">
    				<i class="fa fa-fw fa-lg fa-check-circle"></i> Submit
    			</button>
    		</div>
		</form>
	</div>

</div>
<script>
$(function(){

	$('#student-container').on('click', '#remove-row',function(e){
		if($('.student-rows').length == 1){
			alert("Only one row remaining");
			return;
		};
		var id = $(this).data('id');
		// console.log('id', id);
		$('#student-container').find(`#row-${id}`).remove();
	});

	var currentRowId = 1;
	$('#add-row').on('click', function(e){
		
		currentRowId = currentRowId + 1;

		var html = $($('#row-1').parent().html());
		
		html.find('.firstname').attr('name', `students[${currentRowId}][firstname]`);
		html.find('.lastname').attr('name', `students[${currentRowId}][lastname]`);
		html.find('.grade_id').attr('name', `students[${currentRowId}][grade_id]`);
		// html.find('#row-id').attr('id', `row-${currentRowId}`);
		html.find('#remove-row').attr('data-id', currentRowId);
		// console.log("currentRowId", currentRowId);
		// console.log("html", html);
		$('#student-container').append(
			`<div class="student-rows" id="row-${currentRowId}">`
			+html.html()+'</div>');

	});
});
</script>
@endsection