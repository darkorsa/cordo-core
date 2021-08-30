<?php

namespace Cordo\Core\UI\Validator;

interface ValidatorInterface
{
    public function isValid(array $data, array $customDefaultMessages = null): bool;

    public function messages(): array;
}
