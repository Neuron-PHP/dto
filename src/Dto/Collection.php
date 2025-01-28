<?php

namespace Neuron\Dto;

use Neuron\Dto\CompoundBase;
use Neuron\Log\Log;
use Neuron\Dto\Property;

class Collection extends CompoundBase
{
	private array		$_Children = [];
	private Property	$_ItemTemplate;

	/**
	 * @return Property
	 */

	public function getItemTemplate(): Property
	{
		return $this->_ItemTemplate;
	}

	/**
	 * @param Property $ItemTemplate
	 * @return Collection
	 */

	public function setItemTemplate( Property $ItemTemplate ): Collection
	{
		$this->_ItemTemplate = $ItemTemplate;

		return $this;
	}

	/**
	 * @param ICompound|Property $Child
	 * @return Collection
	 */

	public function addChild( ICompound|Property $Child ) : CompoundBase
	{
		$this->_Children[] = $Child;

		return $this;
	}

	/**
	 * @return array
	 * @throws \Exception
	 */

	public function getChildren() : array
	{
		return $this->_Children;
	}

	/**
	 * @param int $Offset
	 * @return null | ICompound | Property
	 * @throws \Exception
	 */

	public function getChild( int $Offset ) : null | ICompound | Property
	{
		return $this->_Children[ $Offset ] ?? null;
	}
}
