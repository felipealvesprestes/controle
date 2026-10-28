<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

it('requires strong passwords in production', function () {
    app()->detectEnvironment(fn (): string => 'production');

    $validator = Validator::make(['password' => 'weakpass'], ['password' => Password::default()]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('password'))->toContain('12');
});

it('keeps the framework password defaults outside production', function () {
    $validator = Validator::make(['password' => 'weakpass'], ['password' => Password::default()]);

    expect($validator->passes())->toBeTrue();
});

it('limits two-factor attempts per pending login', function () {
    $request = Request::create('/two-factor-challenge', 'POST');
    $request->setLaravelSession(app('session.store'));
    $request->session()->put('login.id', 42);

    $limiter = RateLimiter::limiter('two-factor');
    expect($limiter)->not->toBeNull();

    /** @var Limit $limit */
    $limit = $limiter($request);

    expect($limit->key)->toBe(42)
        ->and($limit->maxAttempts)->toBe(5);
});
