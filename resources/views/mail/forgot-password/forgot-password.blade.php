@component('mail::message')
{{-- Introduction  --}}

<label for="">Hi {{ $user->name }} </label>
<p>
    Someone requested a forgot password for your account.
</p>

{{-- {{ $url }} --}}

@component('mail::button', ['url' => $url ])
{{-- @component('mail::button', ['url' => url('auth/forgot-password', ['email' => $user->email, 'token' => $url]) ]) --}}
Reset password
@endcomponent

<p>If you didn't make this request then you can safely ignore this email.</p>

<br>
Thanks,<br>
{{ env('APP_NAME') }}
@endcomponent