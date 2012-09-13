<?php

namespace MakerBot;

interface MakerInterface
{
	/**
	 * Receives a dependency and returns requirements for this Maker.
	 * 
	 * @param $name name for the object being being passed
	 * @param $obj an instance of the dependency that was required
	 * @return array names of dependencies this maker requires. Can be dynamic.
	 */
	public function injectDependency($name = null, $obj = null);
}