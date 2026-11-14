<?php

declare(strict_types=1);

namespace App\Queries\Dashboard;

use App\Enums\UserRole;
use App\Models\Blog;
use App\Models\User;
use JsonException;

class FontUsageStatsQuery
{
    private const array FONT_THEME_KEYS = [
        '--font-header',
        '--font-body',
        '--font-motto',
        '--font-excerpt',
        '--font-footer',
    ];

    /**
     * @return array{
     *     bloggers: list<array{id: int, name: string}>,
     *     selectedBloggerId: int|null,
     *     fonts: list<array{value: string, label: string, count: int, blogs: list<string>}>
     * }
     * @throws JsonException
     */
    public function handle(?int $bloggerId = null): array
    {
        $fontUsage = [];

        foreach ($this->getFontOptions() as $fontOption) {
            $fontUsage[$fontOption['value']] = [
                'value' => $fontOption['value'],
                'label' => $fontOption['label'],
                'blogs' => [],
            ];
        }

        $blogsQuery = Blog::query()
            ->select(['id', 'user_id', 'name', 'theme'])
            ->orderBy('name');

        if ($bloggerId !== null) {
            $blogsQuery->where('user_id', $bloggerId);
        }

        foreach ($blogsQuery->get() as $blog) {
            $theme = is_array($blog->theme) ? $blog->theme : [];
            $usedFontValues = [];

            foreach (['light', 'dark'] as $variant) {
                $colors = $theme[$variant] ?? [];
                $colors = is_array($colors) ? $colors : [];

                foreach (self::FONT_THEME_KEYS as $fontKey) {
                    $fontValue = $colors[$fontKey] ?? 'inherit';

                    if (is_string($fontValue) && isset($fontUsage[$fontValue])) {
                        $usedFontValues[$fontValue] = true;
                    }
                }
            }

            foreach (array_keys($usedFontValues) as $fontValue) {
                $fontUsage[$fontValue]['blogs'][] = $blog->name;
            }
        }

        $fonts = array_map(static fn(array $font): array => [
            ...$font,
            'count' => count($font['blogs']),
        ], array_values($fontUsage));

        $bloggers = User::query()
            ->whereIn('role', [UserRole::Admin->value, UserRole::Blogger->value])
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(static fn(User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->all();

        return [
            'bloggers' => $bloggers,
            'selectedBloggerId' => $bloggerId,
            'fonts' => $fonts,
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function getFontOptions(): array
    {
        /** @var list<array{label: string, value: string}> $fontOptions */
        $fontOptions = 'data/font-options.json'
                |> resource_path(...)
                |> File(...)
                |> (static fn($x) => json_decode($x, true, flags: JSON_THROW_ON_ERROR));

        return $fontOptions;
    }
}
