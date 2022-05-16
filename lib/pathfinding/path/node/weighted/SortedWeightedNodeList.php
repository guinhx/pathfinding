<?php

namespace lib\pathfinding\path\node\weighted;

use lib\pathfinding\path\node\BlockPathNode;

class SortedWeightedNodeList {
    private array $nodes = [];
    /** @var BlockPathNode[] */
    private array $nodesContainsTester = [];

    public function getAndRemoveFirst(): ?BlockPathNode
    {
        if(count($this->nodes) == 0) return null;
        $result = clone $this->nodes[0];
        unset($this->nodes[0]);
        $this->nodes = array_values($this->nodes);
        $index = array_search($result, $this->nodesContainsTester);
        unset($this->nodesContainsTester[$index]);
        $this->nodesContainsTester = array_values($this->nodesContainsTester);
        return $result;
    }

    public function contains(BlockPathNode $node): bool
	{
        foreach ($this->nodesContainsTester as $n) {
            if($node->equals($n)) return true;
        }
        return false;
	}

    public function getSize(): int
    {
        return count($this->nodes);
    }

    public function getValueToCompare(BlockPathNode $node): int
    {
        return $node->getFValue();
    }

    /** @return BlockPathNode[] */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    public function addSorted(BlockPathNode $node): void
    {
		// don't subtract one from the size since the element could be added after the last current entry
		$this->nodesContainsTester[] = $node;
		$this->insertIntoList($node, 0, $this->getSize());
	}

    private function insertIntoList(BlockPathNode $node, int $lowerBound, int $upperBound): void
    {
        if($lowerBound == $upperBound)
        {
            $this->nodes[$lowerBound] = $node;
            return;
        }

        $nodeValue = $this->getValueToCompare($node);

		$dividingIndex = intval(($lowerBound+$upperBound)/2);
		$dividingNode = $this->nodes[$dividingIndex];
		$dividingValue = $this->getValueToCompare($dividingNode);

		if($nodeValue > $dividingValue)
            $this->insertIntoList($node, $dividingIndex+1, $upperBound);
        else
            $this->insertIntoList($node, $lowerBound, $dividingIndex);
    }

    public function clear(): void
	{
		$this->nodes = [];
        $this->nodesContainsTester = [];
	}

    public function sort(): void
	{
        usort($this->nodes, function ($n1, $n2) {
            return $this->getValueToCompare($n1) > $this->getValueToCompare($n2) ? 1 : -1;
        });
	}
}