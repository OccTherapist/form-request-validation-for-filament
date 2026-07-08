<?php

namespace OccTherapist\FormRequestValidationForFilament;

use Illuminate\Support\Str;

class StatePathNormalizer
{
    /**
     * Strip Filament/Livewire state prefixes so form request keys match field names.
     */
    public function toRelativePath(string $statePath): string
    {
        $path = $statePath;

        foreach ($this->prefixPatterns() as $pattern) {
            $path = preg_replace($pattern, '', $path) ?? $path;
        }

        return $path;
    }

    /**
     * @return array<int, string>
     */
    protected function prefixPatterns(): array
    {
        return [
            '/^mountedActions\.\d+\.data\./',
            '/^mountedFormComponentActions\.\d+\.data\./',
            '/^mountedActionsData\./',
            '/^mountedFormComponentActionsData\./',
            '/^data\./',
            '/^tableFilters\./',
            '/^tableDeferredFilters\./',
        ];
    }

    public function isMountedActionPath(string $statePath): bool
    {
        return Str::startsWith($statePath, 'mountedActions.')
            || Str::startsWith($statePath, 'mountedFormComponentActions.');
    }
}
