<?php
$title = isset($title) ? $title : config('app.name');
?>
@extends('layout')

@section('meta')
<link href="{{asset('css/vali/admin/vali.css')}}" rel="stylesheet">
@endsection

@section('scripts')
<script src="{{asset('js/vali.js')}}"></script>
@endsection
