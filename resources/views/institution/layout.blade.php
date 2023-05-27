<?php
$title = isset($title) ? $title : SITE_TITLE;
?>

@extends('vali_layout')

@section('body')
	
	@include('institution.header')
	
	@include('institution._sidebar')
	
	<main class="app-content">
	@yield('dashboard_content')
	</main>
	
@endsection


