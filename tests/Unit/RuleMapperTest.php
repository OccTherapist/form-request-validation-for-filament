<?php

use OccTherapist\FormRequestValidationForFilament\RuleMapper;

it('maps rules by exact field name', function () {
    $mapper = new RuleMapper;

    $result = $mapper->map(
        ['email' => ['required', 'email']],
        ['data.email'],
    );

    expect($result['matched']['data.email'])->toBe(['required', 'email'])
        ->and($result['orphans'])->toBeEmpty();
});

it('maps rules using wildcard patterns', function () {
    $mapper = new RuleMapper;

    $result = $mapper->map(
        ['items.*.name' => ['required', 'string']],
        ['data.items.0.name', 'data.items.1.name'],
    );

    expect($result['matched']['data.items.0.name'])->toBe(['required', 'string'])
        ->and($result['matched']['data.items.1.name'])->toBe(['required', 'string'])
        ->and($result['orphans'])->toBeEmpty();
});

it('returns orphan rules without matching fields', function () {
    $mapper = new RuleMapper;

    $result = $mapper->map(
        [
            'email' => ['required', 'email'],
            'terms_accepted' => ['accepted'],
        ],
        ['data.email'],
    );

    expect($result['matched']['data.email'])->toBe(['required', 'email'])
        ->and($result['orphans']['terms_accepted'])->toBe(['accepted']);
});

it('strips common state prefixes from field paths', function () {
    $mapper = new RuleMapper;

    expect($mapper->toRelativePath('data.email'))->toBe('email')
        ->and($mapper->toRelativePath('mountedActionsData.title'))->toBe('title');
});
