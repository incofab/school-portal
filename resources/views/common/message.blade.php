<!-- will be used to show any messages -->
@if($errors && $errors->any())
    <div class="alert alert-danger">
    @foreach ($errors->all() as $error)
       <div class="my-1"><small><i class="fa fa-star"></i></small> {{ $error }}</div>
    @endforeach
    </div>
<?php return;?>
@endif

@if (Session::has('message'))
    <div class="alert alert-info">{{ Session::get('message') }}</div>
@endif
@if (Session::has('success'))
    <div class="alert alert-success">{{ Session::get('success') }}</div>
@endif
@if (Session::has('error'))
    <div class="alert alert-danger">{{ Session::get('error') }}</div>
@endif