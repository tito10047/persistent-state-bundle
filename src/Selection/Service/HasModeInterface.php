<?php

namespace Tito10047\PersistentPreferenceBundle\Selection\Service;


use Tito10047\PersistentPreferenceBundle\Enum\SelectionMode;

interface HasModeInterface {
	public function setMode(SelectionMode $mode): void;
	public function getMode(): SelectionMode;
}