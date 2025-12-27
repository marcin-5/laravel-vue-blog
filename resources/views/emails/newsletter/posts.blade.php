<x-mail::message>
# Nowe wpisy na blogu: {{ $blog->name }}

Poniżej znajdziesz listę nowych wpisów, które pojawiły się od ostatniego powiadomienia:

@foreach($posts as $post)
## {{ $post->title }}
{{ $post->excerpt }}

<x-mail::button :url="route('blog.public.post', ['blog' => $blog->slug, 'postSlug' => $post->slug])">
Czytaj więcej
</x-mail::button>
@endforeach

Dziękujemy, że jesteś z nami!
{{ config('app.name') }}
</x-mail::message>
