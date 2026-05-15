<?php

namespace App\Services\Infrastructure;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class FileManagementService
{
    /**
     * Allowed files to be managed/deleted in public path.
     *
     * @var array<int, string>
     */
    private const array ALLOWED_PUBLIC_FILES = [
        'robots.txt',
        'sitemap.xml',
    ];

    /**
     * Delete a specific file from the public directory if it exists and is allowed.
     *
     * @throws InvalidArgumentException
     */
    public function deletePublicFile(string $filename): bool
    {
        $this->validateFilename($filename);

        $path = public_path($filename);

        if (File::exists($path)) {
            return File::delete($path);
        }

        return false;
    }

    /**
     * Validate if the filename is allowed and safe.
     */
    private function validateFilename(string $filename): void
    {
        // Basic path traversal protection
        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            // Special case for localized sitemaps like sitemap-pl.xml
            if (!preg_match('/^sitemap-[a-z]{2}\.xml$/', $filename)) {
                throw new InvalidArgumentException("Invalid filename provided: $filename");
            }
        }

        $isExactMatch = in_array($filename, self::ALLOWED_PUBLIC_FILES, true);
        $isLocalizedSitemap = preg_match('/^sitemap-[a-z]{2}\.xml$/', $filename);

        if (!$isExactMatch && !$isLocalizedSitemap) {
            throw new InvalidArgumentException("File $filename is not on the allowed list for management.");
        }
    }
}
