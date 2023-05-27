@extends('vali_layout')

@section('body')

<section class="material-half-bg">
	<div class="cover"></div>
</section>
<section class="login-content">
	<div class="logo">
		<h1>{{SITE_TITLE}}</h1>
	</div>
	<div class="login-box" style="min-height: 750px; margin-bottom: 30px;">
        <form method="POST" action="{{ route('register') }}" class="login-form">
			<h3 class="login-head">
				<i class="fa fa-lg fa-fw fa-user"></i>REGISTER
			</h3>
        	@include('common.message')
            @csrf
            <div class="form-group">
				<label class="control-label">NAME</label> 
				<input class="form-control" type="text" placeholder="Full Name" autofocus
					name="name" value="{{old('name')}}">
				@error('name')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
			</div>
			<div class="form-group">
				<label class="control-label">USERNAME</label> 
				<input class="form-control" type="text" placeholder="Username" autofocus
					name="username" value="{{old('username')}}">
				@error('username')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
			</div>
            <div class="form-group">
				<label class="control-label">EMAIL</label> 
				<input class="form-control" type="email" placeholder="Email" autofocus
					name="email" value="{{old('email')}}">
				@error('email')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
			</div>
            <div class="form-group">
				<label class="control-label">PHONE</label> 
				<input class="form-control" type="tel" placeholder="Phone number" autofocus
					name="phone" value="{{old('phone')}}">
				@error('phone')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
			</div>
			<div class="form-group">
				<label class="control-label">PASSWORD</label> 
				<input class="form-control" type="password" placeholder="Password"
					name="password">
				@error('password')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
			</div>
			<div class="form-group">
				<label class="control-label">CONFIRM PASSWORD</label> 
				<input class="form-control" type="password" placeholder="Repeat Password"
					name="password_confirmation">
				@error('password_confirmation')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
			</div>
			<div class="semibold-text mb-2 text-right">
				<a href="{{route('login')}}">Goto Login</a>
			</div>
			<div class="form-group btn-container">
				<button class="btn btn-primary btn-block">
					<i class="fa fa-sign-in fa-lg fa-fw"></i>Register
				</button>
			</div>
        </form>
	</div>
</section>


@endsection
