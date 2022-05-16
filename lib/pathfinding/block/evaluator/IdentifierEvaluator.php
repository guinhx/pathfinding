<?php

namespace lib\pathfinding\block\evaluator;

use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\Carpet;
use pocketmine\block\Slab;

class IdentifierEvaluator {
    public static function canStandOn(Block $block): bool
	{
        if($block->getId() == BlockLegacyIds::FENCE) return false;
        if($block->getId() == BlockLegacyIds::SIGN_POST) return false;
        if($block instanceof Slab) return true;
        if($block instanceof Carpet) return true;
		return $block->isSolid();
	}

    public static function canStandIn(Block $block): bool
	{
        if($block->getId() == BlockLegacyIds::SIGN_POST) return true;
        return !$block->isSolid();
    }
}