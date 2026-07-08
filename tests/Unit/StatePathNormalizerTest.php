<?php

use OccTherapist\FormRequestValidationForFilament\StatePathNormalizer;

it('normalizes resource form state paths', function () {
    $normalizer = new StatePathNormalizer;

    expect($normalizer->toRelativePath('data.email'))->toBe('email');
});

it('normalizes mounted action state paths', function () {
    $normalizer = new StatePathNormalizer;

    expect($normalizer->toRelativePath('mountedActions.0.data.email'))->toBe('email')
        ->and($normalizer->toRelativePath('mountedActions.12.data.company'))->toBe('company');
});

it('normalizes nested mounted form component action paths', function () {
    $normalizer = new StatePathNormalizer;

    expect($normalizer->toRelativePath('mountedFormComponentActions.1.data.name'))->toBe('name');
});

it('detects mounted action paths', function () {
    $normalizer = new StatePathNormalizer;

    expect($normalizer->isMountedActionPath('mountedActions.0.data.email'))->toBeTrue()
        ->and($normalizer->isMountedActionPath('data.email'))->toBeFalse();
});
