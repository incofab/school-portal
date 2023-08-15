<?php
$title = 'Admin - All Courses | ' . config('app.name');
$page = 'manage';
$subCat = 'admin';
?>
@extends('ccd.layout')

@section('dashboard_content')

	<div >
		<ol class="breadcrumb">
			<li><a href="{{instRoute('dashboard')}}"><i class="fa fa-dashboard"></i> Dashboard</a></li>
			<li class="active ml-2"><i class="fa fa-users"></i> Courses</li>
		</ol>
		<h4 class="">Admin Available Courses</h4>
	</div>
	<div class="clearfix">
    	<form action="" class="form-inline mt-3 mb-3 float-right" >
    		<div class="form-group">
    			<select name="exam_content" id="" class="form-control" required="required">
    				<option value="">Select ExamContent</option>
    				@foreach($allExamContent as $ec)
    				<option value="{{$ec['id']}}">{{$ec[EXAM_NAME]}}</option>
    				@endforeach
    			</select>
    		</div>
    		<button type="submit" class="btn btn-primary">Submit</button>
    	</form>
	</div>
	<div>
		<table class="table table-striped table-hovered">
			<tr>
				<th>Course Title</th>
				<th>Course Code</th>
                <th>Exam Body</th>
				<th>Topics</th>
				<th>State</th>
			</tr>
			@foreach($courses as $course)
    			<?php $examContent = $course['examContent']; ?>
				<tr>
					<td>{{$course[COURSE_TITLE]}}</td>
					<td>{{$course[COURSE_CODE]}}</td>
					<td>{{$examContent[EXAM_NAME]}}</td>
					<td>
						<a href="{{instRoute('admin_topic_all', [$course['id']])}}" 
							class="btn btn-sm btn-default mr-2"> <i class="fa fa-book"></i> View Topics </a>
					</td>
					<td>
						<a href="{{instRoute('ccd_all_sessions', [$course['id']])}}" 
							class="btn btn-sm btn-default mr-2"> <i class="fa fa-eye"></i> View Content </a>
						<a href="{{instRoute('admin_export_content', [$course['id'], '?next='.instRoute(')])}}" 
							onclick="return confirm('Download {{$course[COURSE_CODE]}} now? \nThis is take a few minutes and should not be interrupted')"
							class="btn btn-sm btn-warning mr-2"> <i class="fa fa-download"></i> Download </a>
    					@if($courseInstaller->isCourseInstalled($course['id']))
    						<a href="{{instRoute('admin_uninstall_course', [$course['id'], '?next='.instRoute(')])}}" 
    							class="btn btn-sm btn-danger"> <i class="fa fa-trash"></i> Uninstall </a>
    						<a href="{{instRoute('admin_install_courses', [$course['id'], '?next='.instRoute(')])}}" 
    							class="btn btn-sm btn-primary"> <i class="fa fa-plus"></i> Upload More </a>
						@else
    						<a href="{{instRoute('admin_install_courses', [$course['id'], '?next='.instRoute(')])}}" 
    							class="btn btn-sm btn-success"> <i class="fa fa-upload"></i> Install </a>
    					@endif
					</td>
				</tr>
			@endforeach
		</table>
	</div>
	

@stop
