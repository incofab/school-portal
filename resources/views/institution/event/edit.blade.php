<?php
$title = "Update Event | " . SITE_TITLE;
?>

@extends('institution.layout')

@section('dashboard_content')

<div class="app-title">
	<div>
		<h1>
			<i class="fa fa-dashboard"></i> Events
		</h1>
		<p>Update Event</p>
	</div>
	<ul class="app-breadcrumb breadcrumb">
		<li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i> <a href="{{route('institution.dashboard', $institution->id)}}">Dashboard</a></li>
		<li class="breadcrumb-item"><a href="{{route('institution.event.index', $institution->id)}}">Events</a></li>
		<li class="breadcrumb-item">Update Event</li>
	</ul>
</div>
@include('common.message')
<div>
	<div class="tile">
		<h3 class="tile-title">Update Event</h3>
		<form action="{{route('institution.event.update', [$institution->id, $oldEvent->id])}}" method="post">
			@csrf
    		@method('PUT')
    		<div class="tile-body">
				<div class="form-group w-75" >
					<label class="control-label">Title</label>
					<input type="text" name="title" value="{{old('title', $oldEvent['title'])}}" class="form-control"
						placeholder="Enter title" />
				</div>
				<div class="form-group w-75" >
					<label class="control-label">Description</label>
					<textarea name="description" class="form-control" 
						rows="3" placeholder="Enter description" >{{old('description', $oldEvent['description'])}}</textarea>
				</div>
				<div class="form-group w-75" >
    				<label class="control-label"><b>Duration</b></label> 
    				<div class="row mx-0">
    <!-- 					<div class="col-md-10 col-lg-9"> -->
    						<div class="form-group col-3 px-1">
    							<label class="control-label">Hours</label> 
    							<input class="form-control" type="text" placeholder="Hours" name="hours" 
    								value="{{old('hours', $oldEvent['hours'])}}" >
    						</div>
    						<div class="form-group col-1 text-center px-1">
    							<label class="control-label">&nbsp;</label> 
    							<div class="form-control">:</div>
    						</div>
    						<div class="form-group col-3 px-1">
    							<label class="control-label">Mins</label> 
    							<input class="form-control" type="text" placeholder="Minutes" name="minutes" 
    								value="{{old('minutes', $oldEvent['minutes'])}}" >
    						</div>
    						<div class="form-group col-1 text-center px-1">
    							<label class="control-label">&nbsp;</label> 
    							<div class="form-control">:</div>
    						</div>
    						<div class="form-group col-3 px-1">
    							<label class="control-label">Seconds</label> 
    							<input class="form-control" type="text" placeholder="Seconds" name="seconds" 
    								value="{{old('seconds', $oldEvent['seconds'])}}" >
    						</div>
    <!-- 					</div> -->
    				</div>
				</div>
				<div class="form-group w-75" >
					<label class="control-label">Subjects</label>
					<select name="course_session_id[]" id="select-subjects" required="required" 
						class="form-control" multiple="multiple" >
						@foreach($subjects as $subject)
						
							<?php 
							$sessions = $subject['sessions'];
							?>
						
    						@foreach($sessions as $acadSession)
    						<option value="{{$acadSession['id']}}" title="{{str_replace('_', ' ', $subject['course_code'])}}"
    							{{in_array($acadSession['id'], $selectedSessionIDs) ? 'selected' : ''}}
    							>{{$subject['course_title'].' '.$acadSession['session']}}</option>
    						@endforeach
    						
						@endforeach
					</select>
				</div>
    		</div>
    		<div class="tile-footer">
    			<button class="btn btn-primary" type="submit">
    				<i class="fa fa-fw fa-lg fa-check-circle"></i> Update
    			</button>
    		</div>
		</form>
	</div>
</div>
<script type="text/javascript" src="{{assets('lib/select2.min.js')}}"></script>
<script type="text/javascript">
$('#select-subjects').select2();
</script>

@endsection