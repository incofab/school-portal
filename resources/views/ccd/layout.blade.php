@extends('ccd.vali_layout')

@section('body')
	
	@include('ccd.header')
	
	@include('ccd._sidebar')
	
	<main class="app-content">
		@include('common.message')
		@yield('dashboard_content')
	</main>
@endsection


