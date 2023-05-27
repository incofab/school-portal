<?php


?>

@extends('ccd.layout')

@section('content')

	 <div class="templatemo-content-widget white-bg ">
	 
		 <header class="text-center">
			<h2>Course Chapter Summary for {{ $courseName }}</h2><hr />
		 </header>

		<?= (empty($errorMsg) ? '' : "<p class=\"report\">$errorMsg</p><hr />" ) ?>
		<form action="" method="post" >
			
			<?= (empty($errors[CHAPTER_NO]) ? '' : "<p class=\"report\">{$errors[CHAPTER_NO][0]}</p>" ) ?>
			<div class="form-group">
			
				<label for="" >Chapter No</label><br />
				<input type="text" name="<?= CHAPTER_NO ?>" value="<?= getValue($post, CHAPTER_NO) ?>" 
					class="form-control"/>
			
			</div> 
			
			<?= (empty($errors[TITLE]) ? '' : "<p class=\"report\">{$errors[TITLE][0]}</p>" ) ?>
			<div class="form-group">
			
				<label for="" >Chapter Title</label><br />
				<textarea name="<?= TITLE ?>" id="" cols="50" rows="2" class="useEditor"><?= getValue($post, TITLE)?></textarea>
			
			</div>   
			
			<?= (empty($errors[DESCRIPTION]) ? '' : "<p class=\"report\">{$errors[DESCRIPTION][0]}</p>" ) ?>
			<div class="form-group">
			
				<label for="" >Description [Optional]</label><br />
				<textarea name="<?= DESCRIPTION ?>" id="" cols="50" rows="4" class="useEditor"><?= getValue($post, DESCRIPTION)?></textarea>
			
			</div>
			 
			<?= (empty($errors[SUMMARY]) ? '' : "<p class=\"report\">{$errors[SUMMARY][0]}</p>" ) ?>
			<div class="form-group">
			
				<label for="" >Summary of the Chapter</label><br />
				<textarea name="<?= SUMMARY ?>" id="" cols="60" rows="15" class="useEditor"><?= getValue($post, SUMMARY)?></textarea>
			
			</div>
			 
			 
			<br /> 
			<div class="form-group">
			
				@if(isset($edit))
					<input type="hidden" name="update_course_summary_chapter" value="true" />
					<input type="hidden" name="<?= TABLE_ID ?>" value="<?= $chapter_id ?>" />
				@else
					<input type="hidden" name="register_course_summary_chapter" value="true" />
				@endif
				
				<input type="hidden" name="<?= COURSE_ID ?>" value="<?= $courseId ?>" />
				<input type="submit" value="submit" class="templatemo-blue-button width-20" /><br /><br />
				
			</div>
					
		</form>

	@include('ccd.common.tinymce')		
		
		
	</div>
			
			
@stop
