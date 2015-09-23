<?php
/**
 * Particle.
 *
 * @link      http://github.com/particle-php for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Particle (http://particle-php.com)
 * @license   https://github.com/particle-php/validator/blob/master/LICENSE New BSD License
 */
namespace Particle\Validator;

/**
 * The MessageStack is responsible for keeping track of all validation messages, including their overwrites.
 *
 * @package Particle\Validator
 */
class MessageStack
{
    /**
     * Contains a list of all validation messages.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Contains an array of field and reason specific message overwrites.
     *
     * @var array
     */
    protected $overwrites = [];

    /**
     * Contains an array of global message overwrites.
     *
     * @var array
     */
    protected $defaultMessages = [];

    /**
     * Will append an error message for the target $key with $reason to the stack.
     *
     * @param string $key
     * @param string $reason
     * @param string $message
     * @param array $parameters
     */
    public function append($key, $reason, $message, array $parameters)
    {
        if (isset($this->defaultMessages[$reason])) {
            $message = $this->defaultMessages[$reason];
        }

        if (isset($this->overwrites[$key][$reason])) {
            $message = $this->overwrites[$key][$reason];
        }

        $this->messages[$key][$reason] = $this->format($message, $parameters);
    }

    /**
     * Returns an overwrite (either default or specific message) for the reason and key, or false.
     *
     * @param string $reason
     * @param string $key
     * @return string|bool
     */
    public function getOverwrite($reason, $key)
    {
        if ($this->hasOverwrite($key, $reason)) {
            return $this->overwrites[$key][$reason];
        }

        if (array_key_exists($reason, $this->defaultMessages)) {
            return $this->defaultMessages[$reason];
        }

        return false;
    }

    /**
     * Returns a list of all messages.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Overwrite key-validator specific messages (so [first_name => [Length::TOO_SHORT => 'Message']]).
     *
     * @param array $messages
     * @return $this
     */
    public function overwriteMessages(array $messages)
    {
        $this->overwrites = $messages;
        return $this;
    }

    /**
     * Overwrite the default validator-specific messages (so [Length::TOO_SHORT => 'Generic message'].
     *
     * @param array $messages
     * @return $this
     */
    public function overwriteDefaultMessages(array $messages)
    {
        $this->defaultMessages = $messages;
        return $this;
    }

    /**
     * Merges an existing MessageStack into this one by taking over it's overwrites and defaults.
     *
     * @param MessageStack $messageStack
     */
    public function merge(MessageStack $messageStack)
    {
        $this->mergeDefaultMessages($messageStack);
        $this->mergeOverwrites($messageStack);
    }

    /**
     * Reset the messages to an empty array.
     *
     * @return $this
     */
    public function reset()
    {
        $this->messages = [];
        return $this;
    }

    /**
     * Formats the message $message with $parameters by replacing {{ name }} with $parameters['name'].
     *
     * @param string $message
     * @param array $parameters
     * @return string
     */
    protected function format($message, array $parameters)
    {
        $replace = function ($matches) use ($parameters) {
            if (array_key_exists($matches[1], $parameters)) {
                return $parameters[$matches[1]];
            }
            return $matches[0];
        };

        return preg_replace_callback('~{{\s*([^}\s]+)\s*}}~', $replace, $message);
    }

    /**
     * Merges the default messages from $messageStack to this MessageStack.
     *
     * @param MessageStack $messageStack
     */
    protected function mergeDefaultMessages(MessageStack $messageStack)
    {
        foreach ($messageStack->defaultMessages as $key => $message) {
            if (!array_key_exists($key, $this->defaultMessages)) {
                $this->defaultMessages[$key] = $message;
            }
        }
    }

    /**
     * Merges the message overwrites from $messageStack to this MessageStack.
     *
     * @param MessageStack $messageStack
     */
    protected function mergeOverwrites(MessageStack $messageStack)
    {
        foreach ($messageStack->overwrites as $key => $reasons) {
            foreach ($reasons as $reason => $message) {
                if (!$this->hasOverwrite($key, $reason)) {
                    $this->overwrites[$key][$reason] = $message;
                }
            }
        }
    }

    /**
     * Returns whether an overwrite exists for the key $key with reason $reason.
     *
     * @param string $key
     * @param string $reason
     * @return bool
     */
    protected function hasOverwrite($key, $reason)
    {
        return isset($this->overwrites[$key][$reason]);
    }
}
