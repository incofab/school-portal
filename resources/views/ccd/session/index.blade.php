
@extends('ccd.layout')

@section('content')

	 <div class="templatemo-content-widget white-bg ">
	
	 <br />
	
	 <a href="<?= route('ccd.session.create', [$institution->id, $course->id]) ?>" class="templatemo-blue-button width-20" >
			<span>Add New Session</span>					
	</a>
	<br class="clear" />
	<br />
	<br />
		
	<div id="table_nav">
	<div class="row">
		<div class="col-sm-6">
    	 	<p class="form-group">
    			<span class="small">Search</span>
				<input type="text" placeholder="search table" class="form-control" onkeyup="filter_table(this, 'table_')" />
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
	</div>
	
	 
	<div class="panel panel-default templatemo-content-widget white-bg no-padding templatemo-overflow-hidden">
		
 	<div class="panel-heading templatemo-position-relative">
 		<h2 class="text-uppercase">Available Sessions for {{$course['course_code']}}</h2>
 	</div>
	<div class="table-responsive">
		<table class="table table-striped table-bordered" id="table_">
			<thead>
				<tr>
					<th><b>No.</b></th>
                    <th><b>Year</b></th>
<!--                     <td><b>Content File</b></td> -->
                    <th><b>Edit</b></th>
                    <th><b>Questions</b></th>
                    <th>Actions</th>
                    <!-- 
                    <td><b>Preview</b></td>
                    <td><b>Delete</b></td>
                     -->
				</tr>
			</thead>
			
			<tbody>
			 
			<?php $i = 0;  ?>
			@foreach($allRecords as $courseSession)
			 
				<tr> 
					<td><?= ++$i; ?></td> 
					<td><?= $courseSession['session'] ?></td>
					<?php /*
					<td><a href="{{route('ccd_download_session_content', [$courseSession[TABLE_ID]])}}" title="Download file"
						onclick="return confirm('Download this file?')" >
						{{(empty($courseSession[FILE_PATH]))?'Not Available':pathinfo($courseSession[FILE_PATH], PATHINFO_FILENAME)}}
						</a>
					</td>
					<td title="<?= $courseSession[GENERAL_INSTRUCTIONS] ?>" ><?= (strlen($courseSession[GENERAL_INSTRUCTIONS]) > 20) 
							? substr($courseSession[GENERAL_INSTRUCTIONS], 0, 20) . '...' 
							: $courseSession[GENERAL_INSTRUCTIONS] ?></td>
					*/?>							
					<td><a href="<?= route('ccd.session.edit', [$institution->id, $course->id, $courseSession->id]) ?>">Edit</a></td>
					<td><a href="<?= route('ccd.question.index', [$institution->id, $courseSession->id]) ?>">Record Questions</a></td>
					<td>
						<a href="<?= route('ccd.session.preview', [$institution->id, $courseSession->id]) ?>">Preview</a>
						|
						<a href="<?= route('ccd.session.upload-excel-question', [$institution->id, $course->id, $courseSession->id]) ?>">Upload</a>
						|
						<a href="<?= route('ccd.session.destroy', [$institution->id, $course->id, $courseSession->id]) ?>"
								onclick="return confirm('Are you sure?')" >Delete</a>
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