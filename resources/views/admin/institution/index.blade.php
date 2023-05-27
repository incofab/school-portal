<?php
$title = "Admin - All Institutions | " . SITE_TITLE;

?>
@extends('admin.layout')

@section('dashboard_content')

<div class="app-title">
	<div>
		<h1>
			<i class="fa fa-dashboard"></i> Institution
		</h1>
		<p>List of all Institutions</p>
	</div>
	<ul class="app-breadcrumb breadcrumb">
		<li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i> <a href="{{route('admin.dashboard')}}">Dashboard</a></li>
		<li class="breadcrumb-item">Institutions</li>
	</ul>
</div>
@include('common.message')
<div class="tile">
    <div class="tile-header clearfix mb-3">
    	<a href="{{route('admin.institution.create')}}" class="btn btn-primary pull-right">
    		<i class="fa fa-plus"></i> New
    	</a>
    </div>
    <div class="tile-body">
    	<table class="table table-hover table-bordered" id="data-table" >
    		<thead>
    			<tr>
    				<th>Code</th>
    				<th>Name</th>
    				<th>Email</th>
    				<th>Phone</th>
    				<th><i class="fa fa-bars"></i></th>
    			</tr>
    		</thead>
			@foreach($allRecords as $record)
				<tr title="Address: {{$record->address}}" >
					<td>{{$record['code']}}</td>
					<td>{{$record['name']}}</td>
					<td>{{$record['email']}}</td>
					<td>{{$record['phone']}}</td>
					<td>
						<i class="fa fa-bars"
						   tabindex="0"
						   role="button" 
                           data-html="true" 
                           data-toggle="popover" 
                           title="Options" 
                           data-placement="bottom"
                           data-content="<div>
                            <div><small><i class='fa fa-user'></i> <a href='{{route('admin.institution.assign-user', [$record['id']])}}' class='btn btn-link'>Assign User</a></small></div>
                            <div><small><i class='fa fa-hand-point-right'></i> <a href='{{route('institution.dashboard', [$record['id']])}}' class='btn btn-link'>Goto Page</a></small></div>
                            <div><small><i class='fa fa-edit'></i> <a href='{{route('admin.institution.edit', [$record['id']])}}' class='btn btn-link'>Edit</a></small></div>
                            {{--
                            @if($record['status'] == STATUS_SUSPENDED)
                                <div><small><i class='fa fa-circle-o'></i> <a href='{{route('admin.institution.unsuspend', [$record['id']])}}' class='btn btn-link'>Unsuspend</a></small></div>
                            @else
                                <div><small><i class='fa fa-circle-o'></i> <a href='{{route('admin.institution.suspend', [$record['id']])}}' class='btn btn-link'>Suspend</a></small></div>
                            @endif
                            --}}
                            <div><small><i class='fa fa-trash'></i> <a href='{{route('admin.institution.destroy', [$record['id']])}}' class='btn btn-link text-danger'>Delete</a></small></div>
                            "></i>
					</td>
				</tr>
			@endforeach
		</table>
	</div>
	<div class="tile-footer">
		@include('common.paginate')
	</div>
</div>

<!-- Data table plugin-->
<script type="text/javascript">
$(function () {
//   $('[data-toggle="popover"]').popover();
  var popOverSettings = {
		    selector: '[data-toggle="popover"]', //Sepcify the selector here
//	 	    content: function () {
//	 	        return $('#popover-content').html();
//	 	    }
	}
	
	$('#data-table').popover(popOverSettings);
})
</script>

@stop
