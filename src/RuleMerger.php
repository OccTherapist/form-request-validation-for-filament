<?php

namespace OccTherapist\FormRequestValidationForFilament;

use Illuminate\Support\Str;

class RuleMerger
{
    /**
     * @param  array<int|string, mixed>  $fieldRules
     * @param  array<int|string, mixed>  $formRequestRules
     * @return array<int, mixed>
     */
    public function merge(array $fieldRules, array $formRequestRules): array
    {
        $normalizedFieldRules = $this->normalize($fieldRules);
        $normalizedFormRequestRules = $this->normalize($formRequestRules);

        $filteredFieldRules = array_values(array_filter(
            $normalizedFieldRules,
            fn (mixed $rule): bool => ! $this->conflictsWithFormRequestRules($rule, $normalizedFormRequestRules),
        ));

        $merged = [...$filteredFieldRules, ...$normalizedFormRequestRules];

        return $this->deduplicate($merged);
    }

    /**
     * @param  array<int|string, mixed>  $rules
     * @return array<int, mixed>
     */
    public function normalize(array $rules): array
    {
        if ($rules === []) {
            return [];
        }

        if (array_is_list($rules)) {
            return array_values($rules);
        }

        $normalized = [];

        foreach ($rules as $key => $rule) {
            if (is_string($key) && is_string($rule)) {
                $normalized[] = "{$key}:{$rule}";

                continue;
            }

            $normalized[] = $rule;
        }

        return array_values($normalized);
    }

    /**
     * @param  array<int, mixed>  $rules
     * @return array<int, mixed>
     */
    protected function deduplicate(array $rules): array
    {
        $seen = [];
        $deduplicated = [];

        foreach ($rules as $rule) {
            $fingerprint = $this->fingerprint($rule);

            if (isset($seen[$fingerprint])) {
                continue;
            }

            $seen[$fingerprint] = true;
            $deduplicated[] = $rule;
        }

        return $deduplicated;
    }

    /**
     * @param  array<int, mixed>  $formRequestRules
     */
    protected function conflictsWithFormRequestRules(mixed $rule, array $formRequestRules): bool
    {
        $ruleName = $this->resolveRuleName($rule);

        if ($ruleName === null) {
            return false;
        }

        $formRequestRuleNames = array_filter(array_map(
            fn (mixed $formRequestRule): ?string => $this->resolveRuleName($formRequestRule),
            $formRequestRules,
        ));

        if (in_array($ruleName, $formRequestRuleNames, true)) {
            return true;
        }

        foreach ($this->exclusiveRuleGroups() as $group) {
            if (! in_array($ruleName, $group, true)) {
                continue;
            }

            foreach ($formRequestRuleNames as $formRequestRuleName) {
                if (in_array($formRequestRuleName, $group, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<int, array<int, string>>
     */
    protected function exclusiveRuleGroups(): array
    {
        return [
            ['required', 'nullable', 'sometimes', 'present'],
        ];
    }

    protected function resolveRuleName(mixed $rule): ?string
    {
        if (is_string($rule)) {
            return Str::before($rule, ':');
        }

        if (is_object($rule)) {
            return $rule::class;
        }

        return null;
    }

    protected function fingerprint(mixed $rule): string
    {
        if (is_string($rule)) {
            return $rule;
        }

        if (is_object($rule)) {
            return $rule::class;
        }

        return serialize($rule);
    }
}
