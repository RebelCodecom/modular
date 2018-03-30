<?php

namespace RebelCode\Modular\Module;

use ArrayAccess;
use Dhii\Data\Container\ContainerFactoryInterface;
use Dhii\Data\Container\NormalizeContainerCapableTrait;
use Dhii\Exception\CreateInternalExceptionCapableTrait;
use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\Exception\CreateOutOfRangeExceptionCapableTrait;
use Dhii\Exception\CreateRuntimeExceptionCapableTrait;
use Dhii\Exception\InternalException;
use Dhii\Factory\Exception\CouldNotMakeExceptionInterface;
use Dhii\Factory\Exception\FactoryExceptionInterface;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Modular\Module\DependenciesAwareInterface;
use Dhii\Modular\Module\ModuleInterface;
use Dhii\Util\Normalization\NormalizeIterableCapableTrait;
use Dhii\Util\Normalization\NormalizeStringCapableTrait;
use Dhii\Util\String\StringableInterface as Stringable;
use Exception;
use InvalidArgumentException;
use OutOfRangeException;
use Psr\Container\ContainerInterface;
use RuntimeException;
use stdClass;

/**
 * Common base functionality for modules.
 *
 * @since [*next-version*]
 */
abstract class AbstractBaseModule implements
    ModuleInterface,
    DependenciesAwareInterface
{
    /*
     * Provides common module functionality.
     *
     * @since [*next-version*]
     */
    use ModuleTrait {
        _getKey as public getKey;
        _getDependencies as public getDependencies;
    }

    /*
     * Provides string normalization functionality.
     *
     * @since [*next-version*]
     */
    use NormalizeStringCapableTrait;

    /*
     * Provides iterable normalization functionality.
     *
     * @since [*next-version*]
     */
    use NormalizeIterableCapableTrait;

    /*
     * Provides container normalization functionality.
     *
     * @since [*next-version*]
     */
    use NormalizeContainerCapableTrait;

    /*
     * Provides functionality for creating invalid-argument exceptions.
     *
     * @since [*next-version*]
     */
    use CreateInvalidArgumentExceptionCapableTrait;

    /*
     * Provides functionality for creating out-of-range exceptions.
     *
     * @since [*next-version*]
     */
    use CreateOutOfRangeExceptionCapableTrait;

    /*
     * Provides functionality for creating internal exceptions.
     *
     * @since [*next-version*]
     */
    use CreateInternalExceptionCapableTrait;

    /*
     * Provides functionality for creating runtime exceptions.
     *
     * @since [*next-version*]
     */
    use CreateRuntimeExceptionCapableTrait;

    /*
     * Provides string translating functionality.
     *
     * @since [*next-version*]
     */
    use StringTranslatingTrait;

    /**
     * The factory to use for creating containers.
     *
     * @since [*next-version*]
     *
     * @var ContainerFactoryInterface
     */
    protected $containerFactory;

    /**
     * Initializes the module with all required information.
     *
     * @since [*next-version*]
     *
     * @param ContainerFactoryInterface                     $containerFactory The container factory.
     * @param string|Stringable                             $key              The module key.
     * @param string[]|Stringable[]                         $dependencies     The module dependencies.
     * @param array|ArrayAccess|stdClass|ContainerInterface $config           The module config.
     */
    protected function _initModule(ContainerFactoryInterface $containerFactory, $key, $dependencies = array(), $config = array())
    {
        $this->_setKey($key);
        $this->_setDependencies($dependencies);
        $this->_setConfig($config);
        $this->_setContainerFactory($containerFactory);
    }

    /**
     * Retrieves the container factory associated with this module.
     *
     * @since [*next-version*]
     *
     * @return ContainerFactoryInterface The container factory instance.
     */
    protected function _getContainerFactory()
    {
        return $this->containerFactory;
    }

    /**
     * Sets the container factory for this module.
     *
     * @since [*next-version*]
     *
     * @param ContainerFactoryInterface $containerFactory The container factory instance.
     */
    protected function _setContainerFactory(ContainerFactoryInterface $containerFactory)
    {
        $this->containerFactory = $containerFactory;
    }

    /**
     * Creates a container instance with the given service definitions.
     *
     * @since [*next-version*]
     *
     * @param callable[]|ArrayAccess|stdClass|ContainerInterface $definitions The service definitions.
     * @param ContainerInterface|null                            $parent      The parent container instance, if any.
     *
     * @return ContainerInterface The created container instance.
     *
     * @throws CouldNotMakeExceptionInterface If the factory failed to create the exception.
     * @throws FactoryExceptionInterface      If the factory encountered an error.
     */
    protected function _createContainer($definitions = array(), ContainerInterface $parent = null)
    {
        return $this->_getContainerFactory()->make(['definitions' => $definitions, 'parent' => $parent]);
    }

    /**
     * Loads a PHP config file and returns the configuration.
     *
     * Since module systems have varying loading mechanisms, it is not safe to assume that the current working directory
     * will be equivalent to the module's directory. Therefore, it is recommended to use absolute paths for the file
     * path argument.
     *
     * @since [*next-version*]
     *
     * @param string|Stringable $filePath The path to the PHP config file. Absolute paths are recommended.
     *
     * @throws InternalException   If an exception was thrown by the PHP config file.
     * @throws OutOfRangeException If the config retrieved from the PHP config file is not a valid container.
     * @throws RuntimeException    If the config file could not be read.
     *
     * @return array|ArrayAccess|stdClass|ContainerInterface The config.
     */
    protected function _loadPhpConfigFile($filePath)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw $this->_createRuntimeException(
                $this->__('Config file does not exist or not readable'),
                null,
                null
            );
        }

        try {
            $config = require $filePath;
        } catch (Exception $exception) {
            throw $this->_createInternalException(
                $this->__('The PHP config file triggered an exception'),
                null,
                $exception
            );
        }

        try {
            return $this->_normalizeContainer($config);
        } catch (InvalidArgumentException $exception) {
            throw $this->_createOutOfRangeException(
                $this->__('The config retrieved from the PHP config file is not a valid container'),
                null,
                null,
                $config
            );
        }
    }
}
