@component('mail::message')
# Hello {{ $user->first_name }}!

Your OTP code is:

@component('mail::panel')
# {{ $otp }}
@endcomponent

This code will expire in {{ $expiresIn }}.

**Do not share this code with anyone.**

@if($purpose === 'email_verification')
Use this code to verify your email address.
@elseif($purpose === 'password_reset')
Use this code to reset your password.
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent