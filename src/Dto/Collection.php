<?php

namespace Neuron\Dto;

use Neuron\Data\Object\NumericRange;
use Neuron\Log\Log;

class Collection extends CompoundBase
{
	private ?NumericRange $_ValidRange = null;
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
		if( $this->_ValidRange !== null )
		{
			if( count( $this->_Children ) >= $this->_ValidRange->Maximum )
			{
				$Message = "Items for {$this->getName()} would exceed the maximum range of {$this->_ValidRange->Maximum}";
				Log::warning( "Items for {$this->getName()} would exceed the maximum range of {$this->_ValidRange->Maximum}" );
				$this->addErrors( [ $Message ] );
			}
		}

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

	public function setRange( int $Min, int $Max ) : Collection
	{
		$this->_ValidRange = new NumericRange( $Min, $Max );

		return $this;
	}
}
