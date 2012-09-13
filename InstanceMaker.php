<?php

namespace MakerBot;

require_once dirname(__FILE__) . '/MakerInterface.php';

abstract class InstanceMaker implements MakerInterface
{
	protected $instance = null;
	
	public function getInstance()
	{
		return $this->instance;
	}
	
	public function setInstance($instance)
	{
		$this->instance = $instance;
	}
}