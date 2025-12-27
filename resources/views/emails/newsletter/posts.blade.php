<x-mail::message>
# Nowe wpisy na Twoich subskrybowanych blogach

Poniżej znajdziesz listę nowych wpisów, które pojawiły się od ostatniego powiadomienia:

---
@foreach($data as $item)
## Blog: {{ $item['blog']->name }}
@foreach($item['posts'] as $post)

### {{ $post->title }}
{{ $post->excerpt }}

<x-mail::button :url="route('blog.public.post', ['blog' => $item['blog']->slug, 'postSlug' => $post->slug])">
Czytaj więcej
</x-mail::button>

@endforeach

---
@endforeach

Dziękujemy, że jesteś z nami!
{{ config('app.name') }}
</x-mail::message>
