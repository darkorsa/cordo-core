<?php


use function Pest\Faker\faker;
use Cordo\Core\UI\Validator\AbstractValidator;

function createValidator(array $data)
{
    return new class($data) extends AbstractValidator
    {
        protected function rules(): array
        {
            return [
                'email' => [
                    'required',
                    'email:filter',
                    'max:50',
                ],
            ];
        }
    };
}

test('validation success', function () {
    $data = [
        'email' => faker()->email,
    ];
    $validator = createValidator($data);

    expect(!$validator->fails())->toBeTrue();
    expect($validator->messages())->toBeEmpty();
});


test('validation fail', function () {
    $data = [
        'email' => 'test@',
    ];
    $validator = createValidator($data);

    expect($validator->fails())->toBeTrue();
    expect($validator->messages()->toArray())->toEqual([
        'email' => ['email' => 'The email must be a valid email address.'],
    ]);
});
