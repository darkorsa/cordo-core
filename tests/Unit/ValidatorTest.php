<?php

use function Pest\Faker\faker;
use Cordo\Core\UI\Validator\AbstractValidator;

function createValidator()
{
    return new class() extends AbstractValidator
    {
        protected function validationRules(): void
        {
            $this->validator
                ->required('email')
                ->email();
        }
    };
}

test('validation success', function () {
    $data = [
        'email' => faker()->email,
    ];
    $validator = createValidator();

    expect($validator->isValid($data))->toBeTrue();
    expect($validator->messages())->toBeEmpty();
});


test('validation fail', function () {
    $data = [
        'email' => 'test@',
    ];
    $validator = createValidator();

    expect($validator->isValid($data))->toBeFalse();
    expect($validator->messages())->toEqual([
        'email' => ['Email::INVALID_VALUE' => 'email must be a valid email address'],
    ]);
});

test('validation fail with custom messages', function () {
    $data = [
        'email' => 'test@',
    ];
    $customMessages = [
        'Email::INVALID_VALUE' => 'invalid email address',
    ];
    $validator = createValidator();

    expect($validator->isValid($data, $customMessages))->toBeFalse();
    expect($validator->messages())->toEqual([
        'email' => ['Email::INVALID_VALUE' => 'invalid email address'],
    ]);
});

test('validator throws exception', function () {
    $validator = createValidator();
    $validator->messages();
})->throws(Exception::class);

test('callback validator', function () {
    $data = [
        'email' => faker()->email,
        'code' => '1234',
    ];
    $validator = createValidator();
    $validator->addCallbackValidator('code', function ($code) {
        if (!strlen($code) < 5) {
            throw new Particle\Validator\Exception\InvalidValueException('value is too short', 'code');
        }

        return true;
    });
    expect($validator->isValid($data))->toBeFalse();
    expect($validator->messages())->toEqual([
        'code' => ['code' => 'value is too short'],
    ]);
});
