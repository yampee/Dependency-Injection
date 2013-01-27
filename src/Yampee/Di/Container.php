<?php

/*
 * Yampee Components
 * Open source web development components for PHP 5.
 *
 * @package Yampee Components
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @link http://titouangalopin.com
 */

/**
 * Depencency Injection container
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class Yampee_Di_Container
{
	/**
	 * @var array $services
	 */
	private $services;

	/**
	 * @var array $tags
	 */
	private $tags;

	/**
	 * @var array $parameters
	 */
	private $parameters;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->services = array();
		$this->tags = array();
		$this->parameters = array();

		$this->set('container', $this);
		$this->set('container.dumper', new Yampee_Di_Dumper());
	}

	/**
	 * Build the container
	 *
	 * @param array $servicesDefinitions
	 * @param array $parameters
	 * @throws InvalidArgumentException
	 */
	public function build(array $servicesDefinitions, array $parameters = array())
	{
		$this->parameters = $parameters;

		foreach($servicesDefinitions as $serviceName => $serviceDefinition) {

			$serviceDefinition = array_merge(array(
				'class' => '',
				'arguments' => array()
			), $serviceDefinition);

			if (! class_exists($serviceDefinition['class']) && isset($serviceDefinition['file'])) {
				require $serviceDefinition['file'];
			}

			if (! class_exists($serviceDefinition['class'])) {
				throw new InvalidArgumentException(sprintf(
					'Class %s not found in service %s.',
					$serviceDefinition['class'], $serviceName
				));
			}

			$arguments = array();
			$i = 1;

			foreach ($serviceDefinition['arguments'] as $argument) {
				if (is_string($argument) && substr($argument, 0, 1) == '%' && substr($argument, -1) == '%') {
					$paramName = substr($argument, 1, -1);

					if (! $this->hasParameter($paramName)) {
						throw new InvalidArgumentException(sprintf(
							'Config element %s does not exists in service definition %s (argument %s)',
							$paramName, $serviceName, $i
						));
					}

					$arguments[] = $this->getParameter($paramName);
				} elseif (is_string($argument) && substr($argument, 0, 1) == '@') {
					$referenceName = substr($argument, 1);

					if (! $this->has($referenceName)) {
						throw new InvalidArgumentException(sprintf(
							'Reference at %s is not available in service definition %s (argument %s)',
							$referenceName, $serviceName, $i
						));
					}

					$arguments[] = $this->get($referenceName);
				} else {
					$arguments[] = $argument;
				}

				$i++;
			}

			$reflection = new ReflectionClass($serviceDefinition['class']);
			$instance = $reflection->newInstanceArgs($arguments);

			if (isset($serviceDefinition['calls']) && ! empty($serviceDefinition['calls'])) {
				foreach ($serviceDefinition['calls'] as $methodName => $methodArgs) {
					$method = new ReflectionMethod($instance, $methodName);
					$method->invokeArgs($instance, $methodArgs);
				}
			}

			if (isset($serviceDefinition['tags']) && ! empty($serviceDefinition['tags'])) {
				foreach ($serviceDefinition['tags'] as $tag) {
					$this->tags[$serviceName][] = $tag;
				}
			}

			$this->set($serviceName, $instance);
		}
	}

	/**
	 * @return array
	 */
	public function getAll()
	{
		return $this->services;
	}

	/**
	 * @param $serviceName
	 * @return mixed
	 */
	public function get($serviceName)
	{
		return $this->services[$serviceName];
	}

	/**
	 * @param $serviceName
	 * @return bool
	 */
	public function has($serviceName)
	{
		return isset($this->services[$serviceName]);
	}

	/**
	 * @param string $serviceName
	 * @param object $object
	 * @return Yampee_Di_Container
	 */
	public function set($serviceName, $object)
	{
		$this->services[$serviceName] = $object;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getAllParameters()
	{
		return $this->parameters;
	}

	/**
	 * @param $paramName
	 * @return mixed
	 */
	public function getParameter($paramName)
	{
		return $this->parameters[$paramName];
	}

	/**
	 * @param $paramName
	 * @return bool
	 */
	public function hasParameter($paramName)
	{
		return isset($this->parameters[$paramName]);
	}

	/**
	 * @param string $paramName
	 * @param mixed $value
	 * @return Yampee_Di_Container
	 */
	public function setParameter($paramName, $value)
	{
		$this->parameters[$paramName] = $value;
		return $this;
	}

	/**
	 * Get the list of tags on a given service
	 *
	 * @param $serviceName
	 * @return array
	 */
	public function getTags($serviceName)
	{
		return $this->tags[$serviceName];
	}

	/**
	 * Check if the given service have the given tag
	 *
	 * @param $serviceName
	 * @param $tagName
	 * @return boolean
	 */
	public function hasTag($serviceName, $tagName)
	{
		return in_array($tagName, $this->tags[$serviceName]);
	}
}