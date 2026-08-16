<?php

declare(strict_types=1);

namespace App\Services\Enneagram;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use JsonException;
use RuntimeException;

final class EnneagramTestDataLoader
{
    /** @var list<string> */
    private const SUPPORTED_LOCALES = ['pl', 'en'];

    /** @var list<string> */
    private const STAGE_1_PARTS = ['part1', 'part2'];

    /** @var list<string> */
    private const STAGE_2_PARTS = ['part1', 'part2', 'part3', 'part4'];

    private readonly string $questionsDirectory;

    private readonly string $configPath;

    public function __construct(
        private readonly Filesystem $filesystem,
        ?string $questionsDirectory = null,
        ?string $configPath = null,
    ) {
        $this->questionsDirectory = $questionsDirectory ?? resource_path('data/enneagram');
        $this->configPath = $configPath ?? resource_path('data/enneagram/config.json');
    }

    /**
     * @return array{locale: string, questions: list<array<string, mixed>>, config: array<string, mixed>}
     */
    public function load(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        if (!in_array($locale, self::SUPPORTED_LOCALES, true)) {
            throw new RuntimeException("Unsupported enneagram locale [{$locale}].");
        }

        $cacheKey = "enneagram.data.{$locale}";
        $load = fn(): array => $this->loadFromFiles($locale);

        if (!(bool) config('enneagram.data_cache.enabled', false)) {
            return $load();
        }

        $ttl = max(1, (int) config('enneagram.data_cache.ttl', 3600));

        return Cache::remember($cacheKey, now()->addSeconds($ttl), $load);
    }

    /**
     * @return array{locale: string, questions: list<array<string, mixed>>, config: array<string, mixed>}
     */
    private function loadFromFiles(string $locale): array
    {
        $questionsPath = "{$this->questionsDirectory}/{$locale}/questions.json";

        if (!$this->filesystem->exists($questionsPath)) {
            throw new RuntimeException('Enneagram questions data is missing.');
        }

        if (!$this->filesystem->exists($this->configPath)) {
            throw new RuntimeException('Enneagram configuration data is missing.');
        }

        $questions = $this->decodeJsonFile($questionsPath, 'questions');
        $config = $this->decodeJsonFile($this->configPath, 'configuration');

        return [
            'locale' => $locale,
            'questions' => $this->validateQuestions($questions),
            'config' => $this->validateConfig($config),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonFile(string $path, string $description): array
    {
        try {
            $decoded = json_decode($this->filesystem->get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Unable to decode enneagram {$description} data.", 0, $exception);
        }

        if (!is_array($decoded)) {
            throw new RuntimeException("Enneagram {$description} data must be an object or list.");
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $questions
     * @return list<array<string, mixed>>
     */
    private function validateQuestions(array $questions): array
    {
        if (!array_is_list($questions) || $questions === []) {
            throw new RuntimeException('Enneagram questions data must be a non-empty list.');
        }

        $validatedQuestions = [];
        $questionIds = [];

        foreach ($questions as $question) {
            if (!is_array($question) || !is_string($question['id'] ?? null) || $question['id'] === '' || !is_string($question['question'] ?? null)) {
                throw new RuntimeException('Each enneagram question must contain a non-empty id and question text.');
            }

            if (isset($questionIds[$question['id']])) {
                throw new RuntimeException("Duplicate enneagram question id [{$question['id']}].");
            }

            $answerLists = $question['answerLists'] ?? null;

            if (!is_array($answerLists) || $answerLists === []) {
                throw new RuntimeException("Question [{$question['id']}] must contain answer lists.");
            }

            $normalizedAnswerLists = [];

            foreach ($answerLists as $category => $options) {
                $category = (string) $category;

                if ($category === '' || !$this->isValidOptions($options)) {
                    throw new RuntimeException("Question [{$question['id']}] contains invalid answer options.");
                }

                $normalizedAnswerLists[$category] = $options;
            }

            if (array_key_exists('priority', $question) && !is_numeric($question['priority'])) {
                throw new RuntimeException("Question [{$question['id']}] contains an invalid priority.");
            }

            $question['answerLists'] = $normalizedAnswerLists;
            $question['priority'] = isset($question['priority']) ? (int) $question['priority'] : 0;
            $questionIds[$question['id']] = true;
            $validatedQuestions[] = $question;
        }

        return $validatedQuestions;
    }

    private function isValidOptions(mixed $options): bool
    {
        if (is_string($options)) {
            return $options !== '';
        }

        if (!is_array($options) || !array_is_list($options) || $options === []) {
            return false;
        }

        foreach ($options as $option) {
            if (!is_string($option) || $option === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function validateConfig(array $data): array
    {
        $testConfig = $data['testConfig'] ?? null;

        if (!is_array($testConfig) || !is_array($testConfig['stages'] ?? null)) {
            throw new RuntimeException('Enneagram configuration must contain testConfig.stages.');
        }

        foreach (['stage1', 'stage2'] as $stage) {
            if (!is_array($testConfig['stages'][$stage] ?? null)) {
                throw new RuntimeException("Enneagram configuration is missing {$stage}.");
            }

            $parts = $stage === 'stage1' ? self::STAGE_1_PARTS : self::STAGE_2_PARTS;

            foreach ($parts as $part) {
                $testConfig['stages'][$stage][$part] = $this->validatePartConfig(
                    $testConfig['stages'][$stage][$part] ?? null,
                    "{$stage}.{$part}",
                );
            }
        }

        return $testConfig;
    }

    /**
     * @return array<string, int|string>
     */
    private function validatePartConfig(mixed $part, string $path): array
    {
        if (!is_array($part)) {
            throw new RuntimeException("Enneagram configuration is missing {$path}.");
        }

        $required = ['maxQuestions', 'maxSkips', 'answersPerQuestion'];

        foreach ($required as $key) {
            if (!array_key_exists($key, $part) || !is_numeric($part[$key])) {
                throw new RuntimeException("Enneagram configuration {$path}.{$key} must be numeric.");
            }
        }

        $normalized = $part;
        $normalized['maxQuestions'] = (int) $part['maxQuestions'];
        $normalized['maxSkips'] = (int) $part['maxSkips'];
        $normalized['answersPerQuestion'] = (int) $part['answersPerQuestion'];

        if ($normalized['maxQuestions'] < 1 || $normalized['maxSkips'] < 0 || $normalized['answersPerQuestion'] < 1) {
            throw new RuntimeException("Enneagram configuration {$path} contains an invalid limit.");
        }

        foreach (['minLead', 'minLeadAlternative', 'fixedQuestions'] as $key) {
            if (array_key_exists($key, $normalized)) {
                if (!is_numeric($normalized[$key])) {
                    throw new RuntimeException("Enneagram configuration {$path}.{$key} must be numeric.");
                }

                $normalized[$key] = (int) $normalized[$key];
            }
        }

        return $normalized;
    }
}
