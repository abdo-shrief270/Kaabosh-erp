<?php

declare(strict_types=1);

use App\Domain\Currency\Models\FxRevaluation;

test('gain calculation: balance 1000 USD, old rate 15.50, new rate 16.00 produces gain of 500 EGP', function () {
    // foreign_balance = 1000 USD
    // original_rate = 15.50 (1 USD = 15.50 EGP)
    // new_rate = 16.00 (1 USD = 16.00 EGP)
    // revalued_balance = 1000 * 16.00 = 16000.00
    // original_functional = 1000 * 15.50 = 15500.00
    // gain_loss = 16000.00 - 15500.00 = 500.00

    $foreignBalance = '1000.00';
    $originalRate = '15.500000';
    $newRate = '16.000000';

    $revaluedBalance = bcmul($foreignBalance, $newRate, 2);
    $originalFunctional = bcmul($foreignBalance, $originalRate, 2);
    $gainLoss = bcsub($revaluedBalance, $originalFunctional, 2);

    expect($revaluedBalance)->toBe('16000.00');
    expect($originalFunctional)->toBe('15500.00');
    expect($gainLoss)->toBe('500.00');
    expect(bccomp($gainLoss, '0.00', 2) > 0)->toBeTrue();
});

test('loss calculation: balance 1000 USD, old rate 16.00, new rate 15.50 produces loss of -500 EGP', function () {
    // foreign_balance = 1000 USD
    // original_rate = 16.00 (1 USD = 16.00 EGP)
    // new_rate = 15.50 (1 USD = 15.50 EGP)
    // revalued_balance = 1000 * 15.50 = 15500.00
    // original_functional = 1000 * 16.00 = 16000.00
    // gain_loss = 15500.00 - 16000.00 = -500.00

    $foreignBalance = '1000.00';
    $originalRate = '16.000000';
    $newRate = '15.500000';

    $revaluedBalance = bcmul($foreignBalance, $newRate, 2);
    $originalFunctional = bcmul($foreignBalance, $originalRate, 2);
    $gainLoss = bcsub($revaluedBalance, $originalFunctional, 2);

    expect($revaluedBalance)->toBe('15500.00');
    expect($originalFunctional)->toBe('16000.00');
    expect($gainLoss)->toBe('-500.00');
    expect(bccomp($gainLoss, '0.00', 2) < 0)->toBeTrue();
});

test('net calculation: total gains minus total losses', function () {
    // Scenario: two accounts
    // Account A: gain of 500.00
    // Account B: loss of -200.00
    // Net = 500.00 + (-200.00) = 300.00

    $totalGain = '0.00';
    $totalLoss = '0.00';

    $lines = [
        ['gain_loss' => '500.00'],   // gain
        ['gain_loss' => '-200.00'],  // loss
    ];

    foreach ($lines as $line) {
        $gl = $line['gain_loss'];
        if (bccomp($gl, '0.00', 2) > 0) {
            $totalGain = bcadd($totalGain, $gl, 2);
        } else {
            $totalLoss = bcadd($totalLoss, $gl, 2);
        }
    }

    $netGainLoss = bcadd($totalGain, $totalLoss, 2);

    expect($totalGain)->toBe('500.00');
    expect($totalLoss)->toBe('-200.00');
    expect($netGainLoss)->toBe('300.00');
});

test('zero change: same rate produces no gain or loss', function () {
    // foreign_balance = 1000 USD
    // original_rate = 15.50
    // new_rate = 15.50
    // revalued_balance = 1000 * 15.50 = 15500.00
    // original_functional = 1000 * 15.50 = 15500.00
    // gain_loss = 15500.00 - 15500.00 = 0.00

    $foreignBalance = '1000.00';
    $originalRate = '15.500000';
    $newRate = '15.500000';

    $revaluedBalance = bcmul($foreignBalance, $newRate, 2);
    $originalFunctional = bcmul($foreignBalance, $originalRate, 2);
    $gainLoss = bcsub($revaluedBalance, $originalFunctional, 2);

    expect($revaluedBalance)->toBe('15500.00');
    expect($originalFunctional)->toBe('15500.00');
    expect($gainLoss)->toBe('0.00');
    expect(bccomp($gainLoss, '0.00', 2) === 0)->toBeTrue();
});

test('FxRevaluation model has correct status helpers', function () {
    $draft = new FxRevaluation(['status' => 'draft']);
    expect($draft->isDraft())->toBeTrue();
    expect($draft->isPosted())->toBeFalse();

    $posted = new FxRevaluation(['status' => 'posted']);
    expect($posted->isDraft())->toBeFalse();
    expect($posted->isPosted())->toBeTrue();
});

test('FxRevaluation default attributes are set correctly', function () {
    $reval = new FxRevaluation;

    expect($reval->functional_currency)->toBe('EGP');
    expect($reval->status)->toBe('draft');
});
