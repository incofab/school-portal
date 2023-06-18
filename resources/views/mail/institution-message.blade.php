@extends('vendor.mail.text.institution')

@section('email-body')
# {{ $subjectTitle }}

{{$message}}

Signed,<br>
Management
@endsection