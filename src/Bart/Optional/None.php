<?php
namespace Bart\Optional;

use Bart\Exceptions\IllegalStateException;

/**
 * Class that represents an absent optional value. This class will not contain a value
 * and will throw exceptions if attempts to retrieve the value are made.
 *
 * This is a singleton class and the instance can be retrieved via the None::instance() method.
 * Generally, this class will be instantiated via the parent Optional class using Optional::absent()
 * or from passing a null value to the Optional::fromNullable() method.
 *
 * When making comparisons using the equals method, the None class will be equal only
 * to itself (an instance of the None class, of which there will only be one).
 *
 * Implementation inspired by:
 * http://nitschinger.at/A-Journey-on-Avoiding-Nulls-in-PHP
 * https://gist.github.com/philix/7312211
 *
 * Class None
 * @package Bart\Optional
 */
class None extends Optional
{

    /** @var None $instance the class instance */
    private static $instance;

    private function __construct()
    {

    }

    /**
     * Instantiates (if not already instantiated) and returns
     * the instance of the None class.
     * @return None
     */
    public static function instance() {

        if (static::$instance === null) {
            static::$instance = new None();
        }

        return static::$instance;
    }

    /**
     * Whether or not the instance is present. Will always return false
     * for None.
     * @return bool
     */
    public function isPresent()
    {
        return false;
    }

    /**
     * Whether the instance is absent. Will always return true
     * for None.
     * @return bool
     */
    public function isAbsent()
    {
        return true;
    }

    /**
     * Throws an exception as None does not contain any gettable value.
     * @return mixed
     * @throws IllegalStateException
     */
    public function get()
    {
        throw new IllegalStateException("Trying to get a nonexistent value.");
    }

    /**
     * Gets the contained reference, or a provided default value if it is absent.
     * Will always return the provided default value in this class as None does
     * not contain any reference.
     * @param mixed $default
     * @return mixed
     */
    public function getOrElse($default)
    {
        return $default;
    }

    /**
     * Gets the contained reference, or null if it is absent. The idea of
     * Optional is to avoid using null, but there may be cases where it is still relevant.
     * In an instance of None, this will always return null.
     * @return mixed
     */
    public function getOrNull()
    {
        return null;
    }

    /**
     * Returns an Optional containing the result of calling $callable on
     * the contained value. If no value exists, as in the case of None, then
     * this method will simply return None. The method will return None
     * if the result of applying $callable to the contained value is null.
     * @param callable $callable
     * @return Some|None
     */
    public function map(Callable $callable)
    {
        return Optional::absent();
    }

    /**
     * Whether the contained equals a provided object. None will always (and only) be equal
     * to an instance of itself.
     * @param Optional $object
     * @return bool
     */
    public function equals(Optional $object)
    {
       return $object instanceof None;
    }
}
