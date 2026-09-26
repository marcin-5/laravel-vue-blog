<x-mail::message>
# {{ __('private_messaging.email.heading') }}

{{ __('private_messaging.email.intro') }}

**{{ __('private_messaging.email.subject_label') }}:** {{ $message->conversation->subject }}  
**{{ __('private_messaging.email.sender_label') }}:** {{ $message->user->name }}

---

{{ $message->content }}

<x-mail::button :url="$conversationUrl">
{{ __('private_messaging.email.open_conversation') }}
</x-mail::button>

{{ __('private_messaging.email.regards') }}<br>
{{ config('app.name') }}
</x-mail::message>
