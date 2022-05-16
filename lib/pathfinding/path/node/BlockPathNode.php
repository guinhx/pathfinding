<?php

namespace lib\pathfinding\path\node;

use pocketmine\world\Position;
use pocketmine\world\World;

class BlockPathNode {

    private ?BlockPathNode $parent = null;
	private int $transitionType = 0;

    private int $weightFromParent = 0;
	private float $heuristicWeight = 0;

    /**
     * @param int $x
     * @param int $y
     * @param int $z
     */
    public function __construct(public int $x, public int $y, public int $z)
    {}

    public function equals(mixed $other): bool
	{
		if(!($other instanceof BlockPathNode))
			return false;
		return $other->x == $this->x && $other->y == $this->y && $other->z == $this->z;
	}

    public function hashCode(): int
	{
		$hash = 0;
		$hash |= ($this->x%4096)<<20; // 12 bits long, in [0;11]
		$hash |= $this->y<<12; // 8 bits (2^8 = 256) long, in [12;19]
		$hash |= ($this->z%4096); // 12 bits long, in [20;31]
		return $hash;
	}

    public function toString(): string
	{
		return "transitionalNode[x={$this->x},y={$this->y},z=$this->z]";
	}

    /**
     * @return ?BlockPathNode
     */
    public function getParent(): ?BlockPathNode
    {
        return $this->parent;
    }

    /**
     * @return int
     */
    public function getTransitionType(): int
    {
        return $this->transitionType;
    }

    public function getGValue(): float
	{
        if(is_null($this->parent)) return $this->weightFromParent;
        return $this->parent->getGValue() + $this->weightFromParent;
	}

    public function getHValue(): float
    {
        return $this->heuristicWeight;
    }

    public function getFValue(): float
    {
        return $this->getGValue() + $this->getHValue();
    }

    public function getPosition(World $world): Position
    {
        return new Position($this->x, $this->y, $this->z, $world);
    }

    public function setParent(?BlockPathNode $parent, int $transitionType, int $additionalWeight): void
	{
		$this->parent = $parent;
		$this->transitionType = $transitionType;
		$this->weightFromParent = $additionalWeight;
	}

    public function setHeuristicWeight(int $heuristicWeight): void
	{
        $this->heuristicWeight = $heuristicWeight;
    }
}