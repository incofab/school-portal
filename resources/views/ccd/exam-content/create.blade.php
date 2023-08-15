<?php
$title = 'Admin - Add Exam Content'; ?>

@extends('ccd.layout')

@section('dashboard_content')

<div id="place-order">
	<div class="app-title">
    	<div >
    		<ol class="breadcrumb">
    			<li><a href="{{instRoute('dashboard')}}"><i class="fa fa-dashboard"></i> Dashboard</a></li>
    			<li class="active ml-2"><i class="fa fa-users"></i> Exam Content</li>
    		</ol>
    		<h4 class="">Register Exam Content</h4>
    	</div>
 	</div>
</div>

<div class="justify-content-center">
	<div class="col-sm-10 col-md-8">
    	<div class="tile">
    		<h2 class="tile-title">Register Exam Content</h2>
        	<form method="POST" action="{{$edit ? instRoute('exam-contents.update', [$edit]) : instRoute('exam-contents.store')}}" name="register">
        		@include('common.form_message')
				@csrf
				@if ($edit)
					@method('PUT')
				@endif
        		<div class="form-group">
        			<label for="">Institution [Optional]</label>
        			<input type="text" id="" name="institution" value="<?= old(
             'institution',
             $edit?->institution
           ) ?>" 
        				placeholder="Institution" class="form-control">
        		</div>
        		<div class="form-group">
        			<label for="">Exam Body/Name <i>(In short)</i></label>
        			<input type="text" id="" name="exam_name" value="<?= old(
             'exam_name',
             $edit?->exam_name
           ) ?>" 
        				placeholder="Exam Body/Name" class="form-control">
        		</div>
        		<div class="form-group">
        			<label for="">Exam Body/Name <i>(In full)</i> [Optional]</label>
        			<input type="text" id="" name="fullname" value="<?= old(
             'fullname',
             $edit?->fullname
           ) ?>" 
        				placeholder="Exam Body/Name in full" class="form-control">
        		</div>
        		<div class="form-group">
        			<input type="submit" value="submit" style="width: 60%; margin: auto;" 
        				class="btn btn-primary btn-block" /><br /><br />
        		</div>
        	</form>
        </div>
	</div>
</div>

@endsection