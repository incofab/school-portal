<?php 
$title = isset($title) ? $title : 'Admin | ' . SITE_TITLE;
?>

@extends('vali_layout')

@section('body')
	
	@include('admin.header')
	
	@include('admin._sidebar')
	@include('common.message')
	
	<main class="app-content">
	@yield('dashboard_content')
	</main>
@endsection


