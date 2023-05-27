<?php

// $instruction = \App\Controllers\CCD\BaseCCD::getInstruction($allInstructions, $questionObj[QUESTION_NO]);

// $passage = \App\Controllers\CCD\BaseCCD::getPassage($allPassages, $questionObj[QUESTION_NO]);

// $topic = $questionObj['topic'];

?>

 <div class="templatemo-content-widget white-bg question-container mt-2 card shadow rounded-0" >
{{-- 
 	@if($instruction)
 	<div>
		<label for="" ><b>Instruction: </b></label> 
		<b>Questions {!!$instruction['from_']!!} - {!!$instruction['to_']!!}</b>
		<div class="text">{!! $instruction['instruction'] !!}</div>
	</div>
	<br />
	@endif
	
 	@if($passage)
 	<div>
		<label for="" ><b>Passage: </b></label>
		<b>Questions {!!$passage['from_']!!} - {!!$passage['to_']!!}</b>
		<div class="text">{!! $passage['passage'] !!}</div>
	</div>
	<br />
	@endif
--}}
	<div>
		<div class="clearfix">
			<div class="pull-left">No: {!! $question['question_no'] !!} </div>
		</div>
		<div class="question-text text mt-2">{!! $question['question'] !!}</div>
	</div>
 	<br />
	<div class="row">
		<div class="col-md-6">
			<label for="" >(A) </label>
			<div class="text">{!! $question['option_a'] !!}</div>
		</div>
		<div class="col-md-6">
			<label for="" >(B) </label>
			<div class="text">{!! $question['option_b'] !!}</div>
		</div>
	</div> 
	<div class="row">
		<div class="col-md-6">
			<label for="" >(C) </label>
			<div class="text">{!! $question['option_c'] !!}</div>
		</div>
		<div class="col-md-6">
			<label for="" >(D) </label>
			<div class="text">{!! $question['option_d'] !!}</div>
		</div>
	</div> 
	<div class="row">
		<div class="col-md-6">
			<label for="" >(E) </label>
			<div class="text">{!! $question['option_e'] !!}</div>
		</div>
	</div>
	<br />
	<div>
		<div style="margin-bottom: 10px;">
			<label for="">Answer: </label> <span>{!! $question['answer'] !!}</span> 
		</div>
		<div id="answer-explanation">
			<label for=""><u>Explanation:</u></label> <span>{!! $question['answer_meta'] !!}</span>
		</div>
	</div> 
	<div style="margin-top: 20px;">
		<a href="<?= route('ccd.question.edit', [$institution->id, $question['id']])?>"
			class="templatemo-blue-button width-20" ><span>Edit</span></a>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<?php 
		  $btnClasses = 'templatemo-blue-button width-20';
		  $deleteRoute = route('ccd.question.destroy', [$institution->id, $question['id']]);
		?>
		@include('common._delete_form')
		<!-- 
		<a href="<?= route('ccd.question.destroy', [$institution->id, $question['id']]) ?>"
			class="templatemo-blue-button width-20" onclick="return confirm('Are you sure?')"
			 ><span>Delete</span></a>	
		 -->
	</div>
</div>

