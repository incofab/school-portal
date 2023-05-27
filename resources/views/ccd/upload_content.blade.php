<?php
$title = 'Upload content';
$post = isset($post) ? $post : [];

?>

@extends('ccd.layout')

@section('content')

	 <div class="templatemo-content-widget white-bg col-2">
	 
		 <header class="text-center">
			<h2>Upload Content</h2><hr />
		 </header>

		<?= (empty($error) ? '' : "<p class=\"report\">$error</p><hr />" ) ?>
		<form action="" method="post" enctype="multipart/form-data">			
			<div class="form-group">
				<label for="" >Course Code</label>
				<select name="{{COURSE_ID}}" id="" class="form-control">
					@foreach($courses as $course)
					<option value="{{$course[TABLE_ID]}}" >{{str_replace('_', ' ', $course[COURSE_CODE])}}</option>
					@endforeach
				</select>
			</div>			
			<?= (empty($errors[SESSION]) ? '' : "<p class=\"report\">{$errors[SESSION][0]}</p>" ) ?>
			<div class="form-group">
				<label for="" >Year</label><br />
				<input type="text" class="form-control" name="<?= SESSION ?>" value="<?= getValue($post, SESSION) ?>"/>
			</div>
			<br />
			<div class="form-group">
				<label for="" >Content</label><br />
				<input type="file" class="form-control" name="content" value="" />
			</div>
			<br />
			<div class="form-group">
				<input type="submit" value="submit" class="templatemo-blue-button width-20" /><br /><br />
			</div>
		</form>
	</div>
			
			
@stop