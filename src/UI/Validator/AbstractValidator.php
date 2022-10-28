<?php

declare(strict_types=1);

namespace Cordo\Core\UI\Validator;

use Cordo\Core\Application\App;
use Illuminate\Validation\Factory;
use Illuminate\Validation\Validator;

abstract class AbstractValidator
{
    protected Validator $validator;

    protected Factory $factory;

    public function __construct(array $data)
    {
        $this->factory = App::getInstance()->validator_factory;

        $this->validator = $this->factory->make($data, $this->rules());
    }

    public function __call(string $method, $params): mixed
    {
        return $this->validator->$method($params);
    }

    abstract protected function rules(): array;
}
