@extends('mobileui.layouts.mobile')

@section('title', 'Login - SIKAT Mobile')
@section('body_class', 'bg-white')

@section('content')
    <!-- App Capsule -->
    <div id="appCapsule" class="pt-0">
        <div class="login-form mt-1">
            <div class="section">
                <img src="{{ asset('assetsmobileui/img/sample/photo/vector4.png') }}" alt="image" class="form-image">
            </div>

            <div class="section mt-1">
                <h1>Get started</h1>
                <h4>Fill the form to log in</h4>
            </div>

            <div class="section mt-1 mb-5">
                <form action="{{ url('/login') }}" method="POST">
                    @csrf

                    <div class="form-group boxed">
                        <div class="input-wrapper">
                            <input type="email" class="form-control" id="email1" name="email"
                                placeholder="Email address" value="{{ old('email') }}" required autocomplete="username">
                            <i class="clear-input">
                                <ion-icon name="close-circle"></ion-icon>
                            </i>
                        </div>
                    </div>

                    <div class="form-group boxed">
                        <div class="input-wrapper">
                            <input type="password" class="form-control" id="password1" name="password"
                                placeholder="Password" required autocomplete="current-password">
                            <i class="clear-input">
                                <ion-icon name="close-circle"></ion-icon>
                            </i>
                        </div>
                    </div>

                    <div class="form-links mt-2">
                        <div>
                            <a href="{{ url('/register') }}">Register Now</a>
                        </div>
                        <div>
                            <a href="{{ url('/forgot-password') }}" class="text-muted">Forgot Password?</a>
                        </div>
                    </div>

                    <div class="form-button-group">
                        <button type="submit" class="btn btn-primary btn-block btn-lg">Log in</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- * App Capsule -->
@endsection

