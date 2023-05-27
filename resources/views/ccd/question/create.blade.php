<?php

$uploadURL = route('ccd.question.upload-image', [$institution->id, $course->id, $session->id]);
$num = $questions->count()+1;
$delayTinyMCE = true;
// dDie($uploadURL);
?>

@extends('ccd.layout')

@section('content')
<style>
#num-div{
    position: fixed; top: 0; right: 0; 
    background-color: #333333aa;
    color: #efefef;
    font-weight: bold;
    font-size: 1.5em;
    padding: 5px 15px;
}
#pending-questions{
    position: fixed; top: 0; left: 0; 
    background-color: #333333aa;
    color: #FF0000;
    font-size: 1em;
    padding: 5px 15px;
}
</style>
	<div id="num-div">No: <span id="num">{{$num}}</span></div>
	<div id="pending-questions"></div>
	<div class="templatemo-content-widget white-bg ">
		<div class="row">
			<div class="col-sm-3">
                <a href="<?= route('ccd.question.index', [$institution->id, $session->id]) ?>" 
                 	class="templatemo-blue-button width-20" title="Go back to question listings" >
                		<i class="fa fa-arrow-left"></i> <span>Back</span>					
                </a>
			</div>
			<div class="col-sm-9 clearfix">
				<div class="pull-right">
				</div>
			</div>
		</div>
        <br />
        <br />
        <header class="text-center">
        	<h2>Enter Question</h2><hr />
        </header>
		
		<form action="{{route('ccd.question.store', [$institution->id, $session->id])}}" method="post" name="record-question" id="record-question-form">
			@csrf
			<div class="row">
				<div class="col-sm-6">
        			<div class="form-group">
        				<label for="" >No: </label>
        				<input type="number" name="question_no" value="{{$num}}" id="question-no" 
        					onchange="questionNumberChanged(this)" required="required" class="form-control w-25"/>
        			</div>
				</div>
			</div>
			
			<div class="form-group">
				<label for="" >Question</label><br />
				<textarea name="question" id="" cols="60" rows="6" class="useEditor" 
					>{{old('question')}}</textarea>
			</div>
			<br/>
			<div class="form-group row">
				<div class="col-md-6">
					<label>A</label><br />
					<textarea name="option_a" cols="30" rows="3" class="useEditor">{{old('option_a')}}</textarea>
				</div>
				<div class="col-md-6">
					<label for="" >B</label><br />
					<textarea name="option_b" cols="30" rows="3"  class="useEditor">{{old('option_b')}}</textarea>
				</div>
			</div> 
			<br/>
			<div class="form-group row">
				<div class="col-md-6">
					<label for="" >C</label><br />
					<textarea name="option_c" cols="30" rows="3" class="useEditor">{{old('option_c')}}</textarea>
				</div>
				<div class="col-md-6">
					<label for="" >D</label><br />
					<textarea name="option_d" cols="30" rows="3" class="useEditor" >{{old('option_d')}}</textarea>
				</div>
			</div> 
			<br/>
			<div class="form-group row">
				<div class="col-md-6">
					<label for="" >E</label><br />
					<textarea name="option_e" cols="30" rows="3" class="useEditor">{{old('option_e')}}</textarea>
				</div>
			</div> 
			<br/>
			<div class="form-group">
				<label for="" >Select Answer: &emsp;</label>
				<select name="answer" id="" required="required" class="form-control w-25">
					<option value="">select Answer</option>
					<option  <?= (old('answer') == 'A') ? 'selected="selected"' : '' ?> >A</option>
					<option  <?= (old('answer') == 'B') ? 'selected="selected"' : '' ?> >B</option>
					<option  <?= (old('answer') == 'C') ? 'selected="selected"' : '' ?> >C</option>
					<option  <?= (old('answer') == 'D') ? 'selected="selected"' : '' ?> >D</option>
					<option  <?= (old('answer') == 'E') ? 'selected="selected"' : '' ?> >E</option>
				</select>
			</div>
			 <br/>
			<div class="form-group">
				<label for="" >Explanation of the answer [optional]</label><br />
				<textarea name="answer_meta" id="" cols="60" rows="6" class="useEditor">{{old('answer_meta')}}</textarea>
			</div> 
			<br /> 
			<div class="form-group">
				<input type="submit" value="Submit" class="templatemo-blue-button width-20" />
			</div>
			<br /><br />
		</form>
	</div>
<script type="text/javascript" src="{{assets('js/add-question.js')}}"></script>
@include('ccd.common.tinymce')
<script type="text/javascript">
	var currentQuestionNo = {{$num}};
	var courseSessionId = {{$session->id}};
	var addQuestionAPI = "{{route('api.ccd.question.create', [$institution->id, $session->id])}}";
	
	function questionNumberChanged(obj) {
		$('#num').text($(obj).val());
	}

	initTinymce();
	
</script>
			
@stop