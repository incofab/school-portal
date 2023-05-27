<?php


?>

@extends('ccd.layout')

@section('content')
	<div class="templatemo-content-widget white-bg">
		 <header class="text-center"> 
			<h2>Register Session</h2><hr />
		 </header>
		 
		<form action="{{route('ccd.session.store', [$institution->id, $courseId])}}" method="post" enctype="multipart/form-data" >
			@csrf
			<div class="form-group">
				<label for="" >Session</label><br />
				<input type="text" name="session" value="{{old('session')}}" class="form-control" />
			</div>
			<div class="form-group">
				<label for="" >Category</label><br />
				<input type="text" name="category" value="{{old('category')}}" class="form-control" />
			</div>
			<div class="form-group">
				<label for="" >General Instructions</label><br />
				<textarea name="general_instructions" id="" cols="60" rows="6" class="wysiwyg form-control"
					>{{old('general_instructions')}}</textarea>
			</div>
			{{--
			<fieldset>
				<legend>Per Question Instructions [if any]</legend>
    			<a href="javascript:addInstruction()" class="templatemo-blue-button width-20" >Add Per Question Instruction</a> <br /><br />
    			<div id="per_question_instruction">
        			@foreach($allInstructions as $instruction)
        				<div class="form-group" ><a href="#" id="remove" >Remove </a>	
        			  		<label for="" >Per Question Instruction</label><br />
        			   		<textarea name="all_instruction['instruction'][]" id="" cols="60" rows="6"
        			    		 ><?= $instruction['instruction'] ?></textarea>
        			   		<br /><br />
        			   		<div>
        				   		<label for="" >From:</label> 
        				   		<input type="number" name="all_instruction[from_][]" size="5" value="{{$instruction['from_']}}" />
        				   		<label for="" >To:</label>
        				   		<input type="number" name="all_instruction[to_][]" size="5" value="{{$instruction['to_']}}" />
        				   		<input type="hidden" name="all_instruction[id][]" value="{{$instruction['id']}}" />
        			   		</div>			   
        	   		   </div>
        	   		 @endforeach
    			</div>
			</fieldset>
			 <hr />
			<fieldset>
				<legend>Passage [if any]</legend>
    			<a href="javascript:addPassage()" class="templatemo-blue-button width-20" >Add Passage</a> <br /><br />
    			<div id="passages">
    				@foreach($allPassages as $passage)
    					<div class="form-group" ><a href="#" id="remove" >Remove </a>	
    							
    				  		<label for="" >Passage</label><br />
    				   		<textarea name="all_passages[passage][]" id="" cols="60" rows="6"
    				    		 >{{$passage['passage']}}</textarea>
    				   		<br /><br />
    				   		<div>
    					   		<label for="" >From:</label> 
    					   		<input type="number" name="all_passages[from_][]" size="5" value="{{$passage['from_']}}" />
    					   		<label for="" >To:</label>
    					   		<input type="number" name="all_passages[to_][]" size="5" value="{{$passage['to_']}}" />
    					   		<input type="hidden" name="all_passages[id][]" value="{{$passage['id']}}" />
    				   		</div>			   
    				   
    		   		   </div>
    		   		 @endforeach
    			</div>
			</fieldset>
			<hr />
			--}}
			<br /> 
			<div class="form-group">
				<input type="submit" value="submit" class="templatemo-blue-button width-20" /><br /><br />
			</div>
					
<!-- 		<script type="text/javascript" src="public/js/lib/tinymce/tinymce.min.js"></script> -->
		<script src="https://cdn.tiny.cloud/1/x5fywb7rhiv5vwkhx145opfx4rsh70ytqkiq2mizrg73qwc2/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
		<script type="text/javascript"> tinymce.init({selector:'textarea.wysiwyg'});</script>
		</form>
	</div>
			
@stop