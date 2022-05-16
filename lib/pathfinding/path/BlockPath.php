<?php

namespace lib\pathfinding\path;

use lib\pathfinding\path\node\BlockPathNode;

class BlockPath {
    /** @var BlockPathNode[] */
    private array $nodes = [];

    public function __construct(BlockPathNode $endNode)
    {
        $this->generatePath($endNode);
    }

    private function generatePath(BlockPathNode $endNode): void
    {
        $currentNode = $endNode;
		while($currentNode != null)
        {
            $this->nodes[] = $currentNode;
            $currentNode = $currentNode->getParent();
        }
        $this->nodes = array_reverse($this->nodes);
    }

    /**
     * @return BlockPathNode[]
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

}