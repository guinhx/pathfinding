
# Pathfinding A*

This is an A* algorithm aimed at developers for plugins on pocketmine.

The algorithm is just a direct rewrite of a version made for Spigot where it can be found on the author section.

## Usage

### Finding path beetween nearby entity and player.

```<?php
use lib\pathfinding\Pathfinder;

$initialPath = $player->getPosition();
$entity = $sender->getWorld()->getNearestEntity($initialPath, 50);

if($entity instanceof Living) {
    try {
        $start = Position::fromObject(
            (clone $initialPath)->subtract(0, -0.5, 0),
            $sender->getWorld()
        );
        $end = Position::fromObject(
            (clone $entity->getPosition())->subtract(0, -0.5, 0),
            $sender->getWorld()
        );
        $pathResult = Pathfinder::find($start, $end);
        $player->sendMessage($pathResult->getDiagnose());
    } catch (\Exception $e) {
        $player->sendMessage('Error! Because, ' . $e->getMessage());
    }
}
```

### Using path result
```
$pathResult = Pathfinder::find($start, $end);
$world = $player->getWorld();
if(!$pathResult->haveFailed()) {
    $nodesPath = $pathResult->getPath()->getNodes();
    $count = count($nodesPath);
    for($i = 0; $i < $count; $i++) {
        $node = $nodesPath[$i];
        $block = VanillaBlocks::IRON();
        // check if is initial or final path
        if($i == 0 || $i == $count-1) {
            $block = VanillaBlocks::EMERALD();
        }
        $world->setBlockAt($node->x, $node->y, $node->z, $block);
    }
}
```



## Authors

- [@guinhx](https://github.com/guinhx) (PMMP Version)
- [@domisum](https://github.com/domisum/CompitumLib) (Spigot Version)
