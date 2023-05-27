<?php
$title = "Institution - All Students | " . SITE_TITLE;
$confirmMsg = 'Are you sure?';
?>
@extends('institution.layout')

@section('dashboard_content')

<div class="app-title">
	<div>
		<h1>
			<i class="fa fa-dashboard"></i> Students
		</h1>
		<p>List of all students in this Institution</p>
	</div>
	<ul class="app-breadcrumb breadcrumb">
		<li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i> <a href="{{route('institution.dashboard', $institution->id)}}">Dashboard</a></li>
		<li class="breadcrumb-item">Students</li>
	</ul>
</div>
@include('common.message')
<div class="tile" id="all-students">
    <div class="tile-header clearfix mb-3">
    	<a href="{{route('institution.student.create', $institution->id)}}" class="btn btn-primary float-left"><i class="fa fa-plus"></i> New</a>
		<div class="form-group row float-right">
			<label for="select-grade" class="col-sm-5 col-form-label">Select Class</label>
			<div class="col-sm-7">
				<select name="grade_id" id="select-grade" class="form-control">
					<option value="">All Classes</option>
					@foreach($allGrades as $grade)
						<option value="{{$grade->id}}" <?= markSelected($grade->id, $gradeId) ?>
						title="{{$grade->description}}" >{{$grade->title}}</option>
					@endforeach
				</select>
			</div>
		</div>
	</div>
    <div class="tile-body">
    	<table class="table table-hover table-bordered" id="data-table" >
    		<thead>
    			<tr>
    				<th><div class="text-center"><input type="checkbox" /></div></th>
    				<th>Student ID</th>
    				<th>Name</th>
    				<th>Class</th>
    				<th>Phone</th>
    				<th>Email</th>
    				<th><i class="fa fa-bars p-2"></i></th>
    			</tr>
    		</thead>
			@foreach($allRecords as $record)
				<?php $grade = $record->grade; ?>
				<tr id="_{{$record['student_id']}}" >
					<td>
						<div class="input-container text-center">
							<label for="{{$record['student_id']}}" class="px-2 py-1 pointer m-0" >
        						<input type="checkbox" class="pointer" id="{{$record['student_id']}}" 
        							data-id="{{$record['student_id']}}"  />
							</label>
        				</div>
					</td>
					<td>{{$record['student_id']}}</td>
					<td>{{$record['lastname']}} {{$record['firstname']}}</td>
					<td>{{Arr::get($grade, 'title')}}</td>
					<td>{{$record['phone']}}</td>
					<td>{{$record['email']}}</td>
					<td>
						<i class="fa fa-bars p-2"
						   tabindex="0"
						   role="button" 
                           data-html="true" 
                           data-toggle="popover" 
                           title="Options" 
                           data-placement="bottom"
                           data-content="<div>
                            <div><small><i class='fa fa-graduation-cap'></i> 
                            <a href='{{route('institution.exam.create', [$institution->id, $record['student_id']])}}' class='btn btn-link'>Register Exam</a></small></div>
                            <div><small><i class='fa fa-edit'></i> 
                            <a href='{{route('institution.student.edit', [$institution->id, $record['id']])}}' class='btn btn-link'>Edit</a></small></div>
                            {{--
                            @if($record['status'] == STATUS_SUSPENDED)
                                <div><small><i class='fa fa-times'></i> 
                                <a onclick='return confirmAction()'  href='{{route('institution.student.unsuspend', [$institution->id, $record['id']])}}' class='btn btn-link'>Unsuspend</a></small></div>
                            @else
                                <div><small><i class='fa fa-times'></i> 
                                <a onclick='return confirmAction()'  href='{{route('institution.student.suspend', [$institution->id, $record['id']])}}' class='btn btn-link'>Suspend</a></small></div>
                            @endif
                            --}}
                            <div><small><i class='fa fa-trash'></i> 
                            <a onclick='return confirmAction()'  href='{{route('institution.student.destroy', [$institution->id, $record['id']])}}' class='btn btn-link text-danger'>Delete</a></small></div>
                            </div>
                            "></i>
					</td>
				</tr>
			@endforeach
		</table>
	</div>
	<div class="tile-footer">
		@include('common.paginate')
	<div class="my-2">
    	<button class="btn btn-danger disabled" id="btn-delete-selected"
    		onclick="handleSubmit('{{STATUS_INVALID}}')" >
    		<i class="fa fa-trash"></i> Delete Selected Students 
    	</button>
    </div>
    <form action="{{route('institution.student.multi-delete', [$institution->id])}}" method="post" id="multi-delete">
    	@csrf
    	<input type="hidden" id="student_ids" name="student_id" value="" />
    </form>
	</div>
</div>

<script type="text/javascript">

$(function () {
  var popOverSettings = {
// 	    placement: 'bottom',
// 	    container: 'body',
// 	    html: true,
	    selector: '[data-toggle="popover"]', //Sepcify the selector here
// 	    content: function () {
// 	        return $('#popover-content').html();
// 	    }
	}
	
	$('#data-table').popover(popOverSettings);
});
function confirmAction() {
	return confirm('{{$confirmMsg}}');
}

var baseUrl = {!!json_encode(route('institution.student.index', $institution->id))!!};

$(function() {

	$('#select-grade').on('change', function(e) {
		
		var selectedGradeId = $(this).val();
		
		if(!selectedGradeId){
			window.location.href = baseUrl;
			return;
		}
		
		window.location.href = baseUrl + '/' + selectedGradeId;
	});
    	
	$('#data-table')
    	.on('change', 'th input', function(e) {
    		var $cb = $(e.target);
    		if($cb.prop('checked')){
    			$('#data-table td .input-container input').prop('checked', true).trigger('change');
    		}else{
    			$('#data-table td .input-container input').prop('checked', false).trigger('change');
    		}
    	});
	$('#data-table')
    	.on('change', 'td .input-container input', function(e) {
    		var $cb = $(e.target);
    		var id = $cb.data('id');
    		if($cb.prop('checked')){
    			$('#data-table tr#_' + id).addClass('table-success');
    			addToSelectedIDs(id);
    		}else{
    			$('#data-table tr#_' + id).removeClass('table-success');
    			removeSelectedIDs(id);
    		}

    		if(selectedId.length > 0){
				$('#btn-delete-selected').removeClass('disabled');
    		}else{
				$('#btn-delete-selected').addClass('disabled');
    		}
    	});
});

var selectedId = [];
function removeSelectedIDs(id) {
	var index = selectedId.indexOf(id);
	
	if(index == -1) return;
	
	selectedId.splice(index, 1);
}

function addToSelectedIDs(id) {
	
	if(selectedId.indexOf(id) != -1) return;
	
	selectedId.push(id);	
}

function handleSubmit(status) {

	if(selectedId.length < 1) return;
	
	if(!confirm('Do you want to delete selected students?')) return false;

	var studentIDs = '';

	var len = selectedId.length;
	
	for (var i = 0; i < len; i++) {

		var id = selectedId[i];
		
		if(i == len-1) studentIDs += id;

		else studentIDs += id+',';
	}

	$('form#multi-delete input[name="student_id"]').val(studentIDs);
	
	$('form#multi-delete').submit();	
}

</script>

@stop
