
@extends('ccd.layout')

@section('content')

<div class="templatemo-content-widget white-bg" >
	<div class="clearfix">
		 <a href="<?= route('ccd.course.create', $institution->id) ?>" class="templatemo-blue-button width-20 pull-left" >
				<span>Register Course</span>					
		 </a>
	</div>
	<br />
	<div id="table_nav">
    	<div class="row">
    		<div class="col-sm-6">
        	 	<p class="form-group">
        			<span class="small">Search</span>&nbsp;&nbsp;
        				<input type="text" placeholder="search table" class="form-control" 
        					onkeyup="filter_table(this, 'table_')" />
        	 	</p>
    	 	</div>
    		<div class="col-sm-6">
        	 	<p class="form-group">
        			<span class="small">No. of Items per Page</span>
        			<select name="" id="select_num_of_rows" class="form-control"
        				onchange="paginate.reArrangePage(this, 'table_', 'paginate_button')">
        				<option >10</option><option >20</option>
        				<option >30</option><option >40</option>
        				<option >50</option><option >60</option>
        			</select>
        		</p>
        	</div>
		</div>
    	<div class="panel panel-default templatemo-content-widget white-bg no-padding templatemo-overflow-hidden">
         	<div class="panel-heading templatemo-position-relative">
         		<h2 class="text-uppercase">All registered courses</h2>
         	</div>
    		<table class="table table-striped table-bordered" id="table_">
    			<thead>
    				<tr>
    					<td><b>No.</b></td>
    					<td><b>ID</b></td>
                        <td><b>Course Code</b></td>
                        <td><b>Course Fullname</b></td>
                        <td></td>
                        <td></td>
    				</tr>
    			</thead>
    			<tbody>
    			<?php $i = 0;  ?>
    			@foreach($allRecords as $regdCourse) 
				<tr> 
					<td><?= ++$i; ?></td> 
					<td><?= $regdCourse['id'] ?></td>
					<td><?= $regdCourse['course_code'] ?></td>
					<td><?= $regdCourse['course_title'] ?></td>
					<td>
						<a href="<?= route('ccd.session.index', [$institution->id, $regdCourse['id']]) ?>">Sessions</a>
						|
						<a href="<?= route('ccd.course.upload', [$institution->id, $regdCourse['id']]) ?>">Upload</a>
						|
						<a href="<?= route('ccd.course.export', [$institution->id, $regdCourse['id']]) ?>"
							onclick="return confirm('Download this content now?')">Download</a>
					</td>
					<td>
						<a href="<?= route('ccd.course.edit', [$institution->id, $regdCourse['id']]) ?>">Edit</a>
						|
						<a href="<?= route('ccd.course.delete', [$institution->id, $regdCourse['id']]) ?>"
							onclick="return confirm('Delete this course if it has no content?')"
								>Delete</a>
						|
						<a href="<?= route('ccd.course.uninstall', [$institution->id, $regdCourse['id']]) ?>"
							onclick="return confirm('WARNING: This will delete this course and every questions/summary recorded under it. Continue?')"
								>Uninstall</a>
					</td>
				</tr>
    			@endforeach
    			</tbody>
    		</table>   
    		<!-- Load pagination and it's buttons -->   
    		<br />
    		<div id="paginate_button">
    			<script type="text/javascript">
    			window.onload = function() {
    				paginate.init('table_', 'paginate_button');
    			}
    			</script>
    		</div> 
    		<br />	 
    	</div>
    </div>
</div>
@stop


