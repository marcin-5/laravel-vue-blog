<?php

namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(3, true);
        $hasFooter = $this->faker->boolean();

        $markdownContent = '## ' . $this->faker->sentence() . "\n\n" .
            $this->faker->paragraphs(2, true) . "\n\n" .
            "```php\n" .
            "echo 'Hello World';\n" .
            "```\n\n" .
            '- ' . $this->faker->word() . "\n" .
            '- ' . $this->faker->word();

        $markdownFooter = $hasFooter ? '### Footer' . $this->faker->sentence() . ' [Link](' . $this->faker->url(
            ) . ')' : null;

        return [
            'user_id' => null,
            'name' => $name,
            'slug' => Str::slug($name),
            'content' => $markdownContent,
            'footer' => $markdownFooter,
            'is_published' => true,
            'locale' => 'pl',
        ];
    }
}
