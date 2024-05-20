<x-mail::message>

Your code for Email Verification is <b>{{ $token }}</b>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
