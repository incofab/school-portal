
@extends('ccd.layout')

@section('content')

	 <div class="templatemo-content-widget white-bg ">
	
	 <br />
	
	 <a href="<?= getAddr('ccd_new_chapter', [$courseId]) ?>" class="templatemo-blue-button width-20" >
			<span>Add New Course Summary Chapter</span>					
	</a>
	<br class="clear" /><br />
		
	 <div id="table_nav">&nbsp;&nbsp;&nbsp;&nbsp;
	 	<p class="left">
			<span class="small">Search</span>&nbsp;&nbsp;
				<input type="text" placeholder="search table" onkeyup="filter_table(this, 'table_')" />
	 	</p>
	 	<p class="right">
			<span>No. of Items per Page</span>
			<select name="" id="select_num_of_rows" 
				onchange="paginate.reArrangePage(this, 'table_', 'paginate_button')">
				<option >10</option><option >20</option>
				<option >30</option><option >40</option>
				<option >50</option><option >60</option>
			</select>
		</p>
		<div class="clear"></div>
	</div>
	
	 
	<div class="panel panel-default templatemo-content-widget white-bg no-padding templatemo-overflow-hidden">
		
 	<div class="panel-heading templatemo-position-relative">
 		<h2 class="text-uppercase">Available Courses Summaries for {{ $courseName }}</h2>
 	</div>
	<div class="table-responsive">
		<table class="table table-striped table-bordered" id="table_">
			<thead>
				<tr>
					<td><b>No.</b></td>
                    <td><b>Chapter No</b></td>
                    <td><b>Title</b></td>
                    <td><b>Description</b></td>
                    <td><b>Edit</b></td>
                    <td><b>Delete</b></td>
				</tr>
			</thead>
			
			<tbody>
			 
			<?php $i = 0;  ?>
			@foreach($allCourseChapterSummary as $courseChapterSummary)
			   
				<tr>
					<td><?= ++$i; ?></td> 
					<td><?= $courseChapterSummary[CHAPTER_NO] ?></td>
					
					<td title="<?= $courseChapterSummary[TITLE] ?>" ><?= (strlen($courseChapterSummary[TITLE]) > 50) 
							? substr($courseChapterSummary[TITLE], 0, 50) . '...' 
							: $courseChapterSummary[TITLE] ?></td>
					
					<td title="<?= $courseChapterSummary[DESCRIPTION] ?>" ><?= (strlen($courseChapterSummary[DESCRIPTION]) > 50) 
							? substr($courseChapterSummary[DESCRIPTION], 0, 50) . '...' 
							: $courseChapterSummary[DESCRIPTION] ?></td>
							
					<td><a href="<?= getAddr('ccd_edit_chapter', 
							[$courseId, $courseChapterSummary[TABLE_ID]]) ?>">Edit</a></td>
					<td><a href="<?= getAddr('ccd_preview_chapter', 
							[$courseId, $courseChapterSummary[TABLE_ID]]) ?>">Preview</a></td>
					<td><a href="<?= getAddr('ccd_delete_chapter', 
							[$courseId, $courseChapterSummary[TABLE_ID]]) ?>"
								onclick="return confirm('Are you sure?')" >Delete</a></td>
					
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