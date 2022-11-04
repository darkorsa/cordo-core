<?php

declare(strict_types=1);

namespace Cordo\Core\UI\Validator;

use Cordo\Core\Application\App;
use Illuminate\Validation\Factory;
use Illuminate\Validation\Validator;

/**
 * @method bool passes()
 * @method bool fails()
 * @method array validate()
 * @method array validated()
 * @method array valid()
 * @method array invalid()
 * @method \Illuminate\Support\MessageBag messages()
 * @method \Illuminate\Support\MessageBag errors()
 * @method array attributes()
 * @method \Illuminate\Validation\Validator setData(array $data)
 * @method array getData()
 * @method mixed getValue()
 * @method array getRules()
 * @method \Illuminate\Validation\Validator setRules(array $rules)
 * @method \Illuminate\Validation\Validator sometimes($attribute, $rules, callable $callback)
 */
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
