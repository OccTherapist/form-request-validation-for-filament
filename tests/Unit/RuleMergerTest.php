<?php

use OccTherapist\FormRequestValidationForFilament\RuleMerger;

it('merges field rules with form request rules', function () {
    $merger = new RuleMerger;

    $merged = $merger->merge(
        ['nullable', 'string'],
        ['required', 'email'],
    );

    expect($merged)->toBe(['string', 'required', 'email']);
});

it('deduplicates identical rules', function () {
    $merger = new RuleMerger;

    $merged = $merger->merge(
        ['required', 'email'],
        ['required', 'email', 'max:255'],
    );

    expect($merged)->toBe(['required', 'email', 'max:255']);
});

it('normalizes associative rule arrays', function () {
    $merger = new RuleMerger;

    expect($merger->normalize(['email' => 'required']))->toBe(['email:required']);
});
