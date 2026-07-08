<?php

namespace OccTherapist\FormRequestValidationForFilament;

use Illuminate\Support\Str;

class RuleMapper
{
    /**
     * @param  array<string, array<int|string, mixed>|string>  $formRequestRules
     * @param  array<int, string>  $fieldPaths
     * @return array{
     *     matched: array<string, array<int, mixed>>,
     *     orphans: array<string, array<int, mixed>>
     * }
     */
    public function map(array $formRequestRules, array $fieldPaths): array
    {
        $matched = [];
        $consumedKeys = [];

        foreach ($fieldPaths as $fieldPath) {
            $relativePath = $this->toRelativePath($fieldPath);
            $ruleKey = $this->findMatchingRuleKey($relativePath, $formRequestRules);

            if ($ruleKey === null) {
                continue;
            }

            $matched[$fieldPath] = app(RuleMerger::class)->normalize($formRequestRules[$ruleKey]);
            $consumedKeys[] = $ruleKey;
        }

        $orphans = [];

        foreach ($formRequestRules as $key => $rules) {
            if (in_array($key, $consumedKeys, true)) {
                continue;
            }

            $orphans[$key] = app(RuleMerger::class)->normalize($rules);
        }

        return [
            'matched' => $matched,
            'orphans' => $orphans,
        ];
    }

    /**
     * @param  array<string, array<int|string, mixed>|string>  $formRequestRules
     */
    public function findMatchingRuleKey(string $relativePath, array $formRequestRules): ?string
    {
        if (array_key_exists($relativePath, $formRequestRules)) {
            return $relativePath;
        }

        $fieldName = Str::afterLast($relativePath, '.');

        if ($fieldName !== $relativePath && array_key_exists($fieldName, $formRequestRules)) {
            return $fieldName;
        }

        foreach (array_keys($formRequestRules) as $ruleKey) {
            if (! str_contains($ruleKey, '*')) {
                continue;
            }

            if (Str::is($ruleKey, $relativePath)) {
                return $ruleKey;
            }
        }

        return null;
    }

    public function toRelativePath(string $statePath): string
    {
        return (string) Str::of($statePath)
            ->replaceFirst('data.', '')
            ->replaceFirst('mountedActionsData.', '')
            ->replaceFirst('mountedFormComponentActionsData.', '');
    }
}
