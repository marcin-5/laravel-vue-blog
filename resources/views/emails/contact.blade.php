<x-mail::message>
    # New Contact Message


    <strong>Name:</strong> {{ $data['name'] }}
    <strong>Email:</strong> {{ $data['email'] }}
    <strong>Subject:</strong> {{ $data['subject'] }}

    ---

    {{ $data['message'] }}

</x-mail::message>
