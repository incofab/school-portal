<?php
$title = 'Admin - Add and Update Topic'; ?>

@extends('ccd.layout')

@section('dashboard_content')
@include('ccd._breadcrumb', ['headerTitle' => 'All Courses', 'crumbs' => [
	breadCrumb('Courses', instRoute('courses.index', $course->exam_content_id)),
	breadCrumb('Topics')->active()
]])
<div>
	<div class="tile">
		<div class="tile-title">{{$edit ? 'Update' : 'Create'}} Topic for ({{$course->code}})</div>
		<form method="POST" action="{{$edit ? instRoute('topics.update', [$edit]) : instRoute('topics.store', [$course])}}" name="register" >
			@include('common.form_message')
			@csrf
			@if ($edit)
				@method('PUT')
			@endif
			<div class="form-group">
				<label for="" >Title</label><br />
				<input type="text" name="title" value="<?= old(
      'title',
      $edit?->title
    ) ?>" class="form-control" placeholder="Topic title" />
			</div>
			<div class="form-group">
				<label for="" >Description</label><br />
				<textarea name="description" id="" cols="30" rows="4" class="form-control"
					placeholder="Topic description" spellcheck="true"><?= old(
       'description',
       $edit?->description
     ) ?></textarea>
			</div>

			<div class="form-group">
				<input type="submit"  name="add" style="width: 60%; margin: auto;" 
						class="btn btn-primary btn-block" value="{{empty($edit) ? 'Add' : 'Update'}}">
				<div class="clearfix"></div>
			</div>
		</form>
	</div>
</div>

@endsection