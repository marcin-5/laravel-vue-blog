@php use App\Models\Post; @endphp
<x-mail::message>
# {{ __('newsletter.email.subject') }}

{{ __('newsletter.email.intro') }}

---
@foreach($data as $item)
## {{ __('newsletter.email.blog_prefix') }} {{ $item['blog']->name }}
@foreach($item['posts'] as $post)
@php
    $isExtension = $post->visibility === Post::VIS_EXTENSION;
    $parentPost = $isExtension ? $post->parentPosts->first() : null;
    $displayTitle = $isExtension && $parentPost
        ? __('newsletter.email.supplement_to') . ' ' . $parentPost->title
        : $post->title;
    $buttonUrl = $isExtension && $parentPost
        ? route('blog.public.post', ['blog' => $item['blog']->slug, 'postSlug' => $parentPost->slug])
        : route('blog.public.post', ['blog' => $item['blog']->slug, 'postSlug' => $post->slug]);
@endphp

### {{ $displayTitle }}
@if($isExtension)
#### {{ $post->title }}
@endif

{{ $post->excerpt }}

<x-mail::button :url="$buttonUrl">
{{ __('newsletter.email.read_more') }}
</x-mail::button>

@endforeach

---
@endforeach

{{ __('newsletter.email.thanks') }}
{{ config('app.name') }}

---
{{ __('newsletter.email.manage_subscription') }}
[{{ __('newsletter.email.manage_link') }}]({{ $manageUrl }})
</x-mail::message>
