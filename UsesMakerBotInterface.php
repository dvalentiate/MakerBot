<?php

namespace MakerBot;

interface UsesMakerBotInterface
{
	public function setMakerBot(\MakerBot\MakerBot $makerBot = null);
}