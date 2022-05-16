<?php

namespace lib\pathfinding;

use lib\pathfinding\block\BlockAStar;
use lib\pathfinding\path\PathResult;
use pocketmine\world\Position;

class Pathfinder {
    /**
     * @throws \Exception
     */
    public static function find(
        Position $start,
        Position $target,
        bool $canUseDiagonalMovement = false,
        bool $canUseLadders = false,
        float $heuristicImportance = 1.0
    ): PathResult
    {
        $pathfinder = new BlockAStar($start, $target);
        $pathfinder->setCanUseDiagonalMovement($canUseDiagonalMovement);
        $pathfinder->setCanUseLadders($canUseLadders);
        $pathfinder->setHeuristicImportance($heuristicImportance);
        $pathfinder->findPath();
        $result = new PathResult();
        $result->setDiagnose($pathfinder->getDiagnose());
        if(!$pathfinder->pathFound())
        {
            $result->setFailure($pathfinder->getFailure());
        } else {
            $result->setPath($pathfinder->getPath());
        }
        return $result;
    }
}