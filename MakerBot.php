<?php

namespace MakerBot;

require_once dirname(__FILE__) . '/MakerInterface.php';

class MakerBot implements MakerInterface
{
	/**
	 * Example:
	 *   app layer: depends on Database and Config
	 *   Database: depends on Config, extended to implement FactoryInterface
	 *   Config: no dependencies
	 * 
	 * The app layer instantiates MakerBot and calls getDependency passing an 
	 * array of the strings 'Database' and 'Config'. MakerBot instantiates
	 * them by looking in the default namespace. Because the Config 
	 * class doesn't implement MakerInterface it is assumed it doesn't
	 * have any dependencies that need managing. Database does implement
	 * MakerInterface and a call to its injectDependency method with no 
	 * params returns an array containing the string 'Config'. The already 
	 * instantiated Config object is then passed to Database by calling its
	 * injectDependency method again this time with the params 'Config' and
	 * the config object. Because the Database's injectDependency method
	 * now does not increase its dependencies in its return value, we are done 
	 * getting all the dependencies for the original getDependency call from
	 * the app layer and an associative array is returned.
	 */
	
	protected $makerNamespace = null;
	
	protected $makerObjMap = array();
	protected $needsResolve = true;
	protected $depMap = array();
	
	public function __construct($makerNamespace = null)
	{
		$this->setMakerNamespace($makerNamespace);
		
		$this->addMaker('MakerBot', $this);
	}
	
	/**
	 * @return array of names
	 */
	public function getResolvedMakerNames()
	{
		$nameSet = array();
		foreach (array_keys($this->makerObjMap) as $name) {
			if (!isset($this->depMap[$name])) {
				$nameSet[] = $name;
			}
		}
		return $nameSet;
	}
	
	/**
	 * @param $name string
	 * @return object
	 */
	public function getMaker($name)
	{
		$this->resolve();
		
		if (isset($this->makerObjMap[$name])) {
			return $this->makerObjMap[$name];
		}
		
		$this->addMaker($name);
		$this->resolve();
		return $this->makerObjMap[$name];
	}
	
	/**
	 * @param $name string | obj  name of the Maker to add or if it is an object the 
	 * 		name will be determined from its class name
	 * @param $obj !null anything except null
	 * @return $this
	 */
	public function addMaker($name, $obj = null)
	{
		if (isset($this->makerObjMap[$name])) {
			throw new \Exception('maker already added using addMaker or getDependency');
		}
		
		if (is_object($name) && $obj === null) {
			// only an object has been passed as a parameter, determine its name
			$obj = $name;
			$name = get_class($name);
			
		} elseif ($obj === null) {
			// object wasn't passed try to create one
			$class = $this->getMakerNamespace() . $name;
			$obj = new $class();
		}
		
		$this->makerObjMap[$name] = $obj;
		
		if ($obj instanceof MakerInterface) {
			// get the depencies that the added maker may have
			$this->appendMakerDependency($name, $obj->injectDependency());
		}
		
		return $this;
	}
	
	/**
	 * @return $this
	 */
	public function resolve()
	{
		while (!empty($this->depMap)) {
			$resolvedDepSet = array();
			
			// loop through map of makers with dependencies, subject
			foreach ($this->depMap as $name => $depSet) {
				$resolved = true;
				
				// go through the subject's dependencies and see if they can be injected into subject
				foreach (array_filter($depSet) as $depName => $state) { // because of filter state is always true
					
					if (isset($this->depMap[$depName])) {
						// this dependency has a dependency that is unresolved
						$resolved = false;
						continue;
					}
					
					// inject into the subject the dependency that doesn't have any depencies itself
					$newDepSet = $this->makerObjMap[$name]->injectDependency($depName, $this->makerObjMap[$depName]);
					// mark the subjects depency as satisfied
					$this->depMap[$name][$depName] = false;
					
					// if there are new dependencies from inject, then subject is not resolved
					$hasNewDep = $this->appendMakerDependency($name, $newDepSet);
					if ($hasNewDep) {
						$resolved = false;
					}
				}
				
				if ($resolved) {
					unset($this->depMap[$name]);
					break; // out of foreach, to the map while loop
				}
			}
		}
		
		return $this;
	}
	
	protected function appendMakerDependency($name, $depSet)
	{
		if (empty($depSet)) {
			// nothing to do
			return false;
		}
		
		// has dependencies, could have a new one
		
		$hasNewDep = false;
		
		if (!isset($this->depMap[$name])) {
			// depMap not defined, do so
			$this->depMap[$name] = array();
		}
		
		foreach ($depSet as $depName) {
			if (!isset($this->depMap[$name][$depName])) {
				// a new dependency
				$this->depMap[$name][$depName] = true;
				$hasNewDep = true;
				
				if (!isset($this->makerObjMap[$depName])) {
					// the depency also hasn't been defined, do that
					$this->addMaker($depName);
				}
			}
		}
		
		return $hasNewDep;
	}
	
	/**
	 * @param $namespace string
	 * @return $this
	 */
	public function setMakerNamespace($namespace)
	{
		if ($namespace) {
			$this->makerNamespace = $namespace;
		} else {
			$this->makerNamespace = __NAMESPACE__ . '\\';
		}
		
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getMakerNamespace()
	{
		if (!$this->makerNamespace) {
			$this->setMakerNamespace(null);
		}
		
		return $this->makerNamespace;
	}
	
	public function injectDependency($name = null, $obj = null)
	{
		return array();
	}
}
