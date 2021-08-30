<?php

declare(strict_types=1);

namespace Cordo\Core\UI\Validator;

use Exception;
use Particle\Validator\Validator;
use Particle\Validator\ValidationResult;

abstract class AbstractValidator implements ValidatorInterface
{
    protected Validator $validator;

    protected ?ValidationResult $result = null;

    public function __construct()
    {
        $this->validator = new Validator();
    }

    public function addCallbackValidator(string $field, callable $callback)
    {
        $this->validator->required($field)->callback($callback);
    }

    public function isValid(array $data, array $customDefaultMessages = null): bool
    {
        $this->validationRules();

        if ($customDefaultMessages) {
            $this->validator->overwriteDefaultMessages($customDefaultMessages);
        }

        $this->result = $this->validator->validate($data);

        return $this->result->isValid();
    }

    public function messages(): array
    {
        if (!$this->result) {
            throw new Exception('No validation messages. Execute validate method first!');
        }

        return $this->result->getMessages();
    }

    abstract protected function validationRules(): void;
}
