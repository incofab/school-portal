<?php 

$lastCourseYearQuestions = $questions->last();

$questionNum = 1;

if($lastCourseYearQuestions) $questionNum = $lastCourseYearQuestions['question_no'] + 1;

?>

@extends('ccd.layout')

@section('content')
		
<div class="templatemo-content-widget white-bg ">
	<a href="<?= route('ccd.question.create', [$institution->id, $session->id]) ?>" 
		class="templatemo-blue-button width-20 pull-right" >
		<i class="fa fa-plus"></i> <span>Add Question</span>
	</a>
	<div class="clearfix"></div>
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
			<span>No. of Items per Page</span>
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
     		<h2 class="text-uppercase">All {{ $course->course_code }} questions for {{ $session->session }}</h2>
     	</div>
    	<div class="table-responsive">
    		<table class="table table-striped table-bordered" id="table_">
    			<thead>
    				<tr>
    					<td><b>No.</b></td>
                        <td><b>Question No.</b></td>
                        <td><b>Question</b></td>
                        <td><b>Edit</b></td>
                        <td><b>Delete</b></td>
                        <td><b>Preview</b></td>
    				</tr>
    			</thead>
    			
    			<tbody>
    			 
    			<?php $i = 0; $num = 0;  ?>
    			@foreach($questions as $question) 
    			   <?php $questionStriped = (strlen($question['question']) > 70) 
    							? substr($question['question'], 0, 70) . '...' 
    							: $question['question'];
						$deleteRoute = route('ccd.question.destroy', [$institution->id, $question['id']])
    			   ?>
    				<tr> 
    					<td><?= ++$i; ?></td> 
    					<td><?= $num = $question['question_no'] ?></td>
    					<td title="" >{{htmlentities($questionStriped)}}</td>
    					<td><a href="<?= route('ccd.question.edit', [$institution->id, $question['id']]) ?>">Edit</a></td>
    					<td><a href="<?= route('ccd.question.show', [$institution->id, $question['id']]) ?>">Preview</a></td>
    					<td>@include('common._delete_form')
						<!-- 
    						<a href="<?= route('ccd.question.destroy', [$institution->id, $question['id']]) ?>"
    								onclick="return confirm('Are you sure?')" >Delete</a>
						 -->
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