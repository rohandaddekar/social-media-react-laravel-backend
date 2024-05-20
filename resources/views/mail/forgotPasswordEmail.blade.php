<x-mail::message>

Please click the button below to reset your password:

<x-mail::button :url="config('app.url') . '/reset-password/' . $token">
  Click here
</x-mail::button>

Or copy the link below and paste it in your browser:
{{ config('app.url') }}/reset-password/{{ $token }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
