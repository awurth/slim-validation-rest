<?php

namespace Awurth\Slim\Rest\Validation;

use Psr\Http\Message\RequestInterface as Request;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as V;
use InvalidArgumentException;
use ReflectionClass;

/**
 * Validator
 *
 * @author  Alexis Wurth <alexis.wurth57@gmail.com>
 * @package Awurth\Slim\Rest\Validation
 */
class Validator
{
    /**
     * List of validation errors
     *
     * @var array
     */
    protected $errors;

    /**
     * The validated data
     *
     * @var array
     */
    protected $data;

    /**
     * @var array
     */
    protected $defaultMessages;

    /**
     * Create new Validator
     *
     * @param array $defaultMessages
     */
    public function __construct(array $defaultMessages = [])
    {
        $this->defaultMessages = $defaultMessages;
    }

    /**
     * Validate request params with the given rules
     *
     * @param Request $request
     * @param array $rules
     * @param array $messages
     * @return $this
     */
    public function validate(Request $request, array $rules, array $messages = [])
    {
        foreach ($rules as $param => $options) {
            $value = $request->getParam($param);
            $this->data[$param] = $value;
            $isRule = $options instanceof V;

            try {
                if ($isRule) {
                    $options->assert($value);
                } else {
                    if (!isset($options['rules']) || !($options['rules'] instanceof V)) {
                        throw new InvalidArgumentException('Validation rules are missing');
                    }

                    $options['rules']->assert($value);
                }
            } catch (NestedValidationException $e) {
                $paramRules = $isRule ? $options->getRules() : $options['rules']->getRules();

                // Get the names of all rules used for this param
                $rulesNames = [];
                foreach ($paramRules as $rule) {
                    $rulesNames[] = lcfirst((new ReflectionClass($rule))->getShortName());
                }

                // If the 'message' key exists, set it as only message for this param
                if (!$isRule && isset($options['message']) && is_string($options['message'])) {
                    $this->errors[$param] = [$options['message']];
                    return $this;
                } else { // If the 'messages' key exists, override global messages
                    $params = [
                        $e->findMessages($rulesNames)
                    ];

                    // If default messages are defined
                    if (!empty($this->defaultMessages)) {
                        $params[] = $e->findMessages($this->defaultMessages);
                    }

                    // If global messages are defined
                    if (!empty($messages)) {
                        $params[] = $e->findMessages($messages);
                    }

                    // If individual messages are defined
                    if (!$isRule && isset($options['messages'])) {
                        $params[] = $e->findMessages($options['messages']);
                    }

                    $this->errors[$param] = array_values(array_filter(call_user_func_array('array_merge', $params)));
                }
            }
        }

        return $this;
    }

    /**
     * Add an error for param
     *
     * @param string $param
     * @param string $message
     * @return $this
     */
    public function addError($param, $message)
    {
        $this->errors[$param][] = $message;
        return $this;
    }

    /**
     * Add errors for param
     *
     * @param string $param
     * @param array $messages
     * @return $this
     */
    public function addErrors($param, array $messages)
    {
        foreach ($messages as $message) {
            $this->errors[$param][] = $message;
        }

        return $this;
    }

    /**
     * Get all errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Set all errors
     *
     * @param array $errors
     * @return $this
     */
    public function setErrors(array $errors)
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * Get errors of param
     *
     * @param string $param
     * @return array
     */
    public function getErrorsOf($param)
    {
        return isset($this->errors[$param]) ? $this->errors[$param] : [];
    }

    /**
     * Set errors of param
     *
     * @param string $param
     * @param array $errors
     * @return $this
     */
    public function setErrorsOf($param, array $errors)
    {
        $this->errors[$param] = $errors;
        return $this;
    }

    /**
     * Get first error of param
     *
     * @param string $param
     * @return string
     */
    public function getFirst($param)
    {
        return isset($this->errors[$param][0]) ? $this->errors[$param][0] : '';
    }

    /**
     * Get the value of a parameter in validated data
     *
     * @param string $param
     * @return string
     */
    public function getValue($param)
    {
        return isset($this->data[$param]) ? $this->data[$param] : '';
    }

    /**
     * Set the value of parameters
     *
     * @param array $data
     * @return $this
     */
    public function setValues(array $data)
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    /**
     * Set validator data
     *
     * @param array $data
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get validated data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Return true if there is no error
     *
     * @return bool
     */
    public function isValid()
    {
        return empty($this->errors);
    }
}
