<?php

namespace Neuron\Dto;

use Neuron\Data\Object\NumericRange;
use Neuron\Dto\Compound\Base;
use Neuron\Dto\Compound\ICompound;

class Collection extends Base
{
	private ?NumericRange $validRange = null;
	private array		$children = [];
	private Property	$itemTemplate;

	/**
	 * @return Property
	 */

	public function getItemTemplate(): Property
	{
		return $this->itemTemplate;
	}

	/**
	 * @param Property $itemTemplate
	 * @return Collection
	 */

	public function setItemTemplate( Property $itemTemplate ): Collection
	{
		$this->itemTemplate = $itemTemplate;

		return $this;
	}

	/**
	 * @param ICompound|Property $child
	 * @return Collection
	 */

	public function addChild( ICompound|Property $child ) : Base
	{
		if( $this->validRange !== null )
		{
			if( count( $this->children ) >= $this->validRange->maximum )
			{
				$message = "Items for {$this->getName()} would exceed the maximum range of {$this->validRange->maximum}";
				$this->addErrors( [ $message ] );
			}
		}

		$this->children[] = $child;

		return $this;
	}

	/**
	 * @return array
	 * @throws \Exception
	 */

	public function getChildren() : array
	{
		return $this->children;
	}

	/**
	 * @param int $offset
	 * @return null | ICompound | Property
	 * @throws \Exception
	 */

	public function getChild( int $offset ) : null | ICompound | Property
	{
		return $this->children[ $offset ] ?? null;
	}

	/**
	 * @param int $min
	 * @param int $max
	 * @return $this
	 */

	public function setRange( int $min, int $max ) : Collection
	{
		$this->validRange = new NumericRange( $min, $max );

		return $this;
	}

	/**
	 * @return string
	 * @throws \Exception
	 */

	public function getAsJson() : string
	{
		$result = '[';

		foreach( $this->getChildren() as $property )
		{
			if( $this->getItemTemplate()->getType() == 'object' )
			{
				$json = $property->getAsJson();

				if( $json )
				{
					$result .= $json . ',';
				}
			}
			else
			{
				$result .= "\"{$property->getValue()}\",";
			}
		}

		if( strlen( $result ) > 1 )
			$result = substr($result, 0, -1);

		return $result.']';
	}
}
