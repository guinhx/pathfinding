<?php

namespace lib\pathfinding\block\evaluator;

use lib\pathfinding\path\node\BlockPathNode;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\world\World;

class StairEvaluator {
    public static function isStair(BlockPathNode $to, World $world): bool
    {
        $stairPosition = $to->getPosition($world)->add(0, -1, 0);
        $stairBlock = $world->getBlock($stairPosition);

        if($stairBlock instanceof Stair || $stairBlock instanceof Slab) {
            return true;
        }
        return false;
    }
}