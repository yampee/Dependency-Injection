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
 * Depencency Injection YAML driver
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class Yampee_Di_Bridge_Yaml_Driver
{
	/**
	 * @var Yampee_Di_Container
	 */
	protected $container;

	/**
	 * Constructor
	 *
	 * @param Yampee_Di_Container $container
	 */
	public function __construct(Yampee_Di_Container $container = null)
	{
		if (empty($container)) {
			$container = new Yampee_Di_Container();
		}

		$this->container = $container;
	}

	/**
	 * Load a YAML file of services definitions into the container.
	 *
	 * @param string $filename
	 * @return Yampee_Di_Bridge_Yaml_Driver
	 */
	public function load($filename)
	{
		$yaml = new Yampee_Yaml_Yaml();
		$content = $yaml->load($filename);

		$this->container->registerDefinitions($content['definitions']);

		foreach ($content['parameters'] as $name => $value) {
			$this->container->setParameter($name, $value);
		}

		return $this;
	}

	/**
	 * @return Yampee_Di_Container
	 */
	public function getContainer()
	{
		return $this->container;
	}
}