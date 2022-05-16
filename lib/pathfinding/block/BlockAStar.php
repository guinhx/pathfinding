<?php

namespace lib\pathfinding\block;

use lib\pathfinding\block\evaluator\IdentifierEvaluator;
use lib\pathfinding\block\evaluator\StairEvaluator;
use lib\pathfinding\path\BlockPath;
use lib\pathfinding\path\node\BlockPathNode;
use lib\pathfinding\path\node\TransitionType;
use lib\pathfinding\path\node\weighted\SortedWeightedNodeList;
use pocketmine\block\Ladder;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class BlockAStar {
    const CLIMBING_EXPENSE = 2;

    private float $heuristicImportance = 1.0;
	private int $maxNodeVisits = 500;
    private int $maxRetry = 30;
    private int $retryCount = 0;

	private bool $canUseDiagonalMovement = true;
	private bool $canUseLadders = true;

	// STATUS
	private bool $moveDiagonally = true;
	private bool $useLadders = false;

    protected ?BlockPathNode $endNode = null;

    private SortedWeightedNodeList $unvisitedNodes;
    private array $visitedNodes = [];

    private float $pathfindingStartNano = 0;
	private float $pathfindingEndNano = 0;

	// OUTPUT
	private ?BlockPath $path = null;
	private string $failure = "";

    public function __construct(private Position $startPosition, private Position $endPosition)
	{
        $this->unvisitedNodes = new SortedWeightedNodeList();
    }

    public function pathFound(): bool
	{
		return !is_null($this->path);
	}

    public function getPath(): BlockPath
    {
        return $this->path;
    }

    public function getFailure(): string
    {
        return $this->failure;
    }

    private function getNanoDuration(): float
    {
		return $this->pathfindingEndNano - $this->pathfindingStartNano;
	}

    private function getMsDuration(): float
    {
		return round($this->getNanoDuration(), 2);
	}

    public function getDiagnose(): string
    {
        $found = $this->pathFound() ? 'y' : 'n';
        $diagnose = "found={$found}, ";
        $visited = count($this->visitedNodes);
		$diagnose .= "visitedNodes={$visited}, ";
		$diagnose .= "unvisitedNodes={$this->unvisitedNodes->getSize()}, ";
		$diagnose .= "retryCount={$this->retryCount}, ";
		$diagnose .= "durationMs={$this->getMsDuration()}, ";
		return $diagnose;
    }

    public function setHeuristicImportance(float $heuristicImportance): void
	{
		$this->heuristicImportance = $heuristicImportance;
	}

    public function setCanUseDiagonalMovement(bool $canUseDiagonalMovement): void
    {
        $this->canUseDiagonalMovement = $canUseDiagonalMovement;
    }

    public function setCanUseLadders(bool $canUseLadders): void
    {
        $this->canUseLadders = $canUseLadders;
    }

    public function findPath(): void
    {
        if($this->pathfindingStartNano == 0) $this->pathfindingStartNano = microtime(true);
        if($this->startPosition->getWorld()->getDisplayName() != $this->endPosition->getWorld()->getDisplayName())
            throw new \InvalidArgumentException("The start and the end location are not in the same world!");
        $startNode = new BlockPathNode(
            $this->startPosition->getFloorX(),
            $this->startPosition->getFloorY(),
            $this->startPosition->getFloorZ()
        );
        $startNode->setParent(null, TransitionType::WALK, 0);
        $this->endNode = new BlockPathNode(
            $this->endPosition->getFloorX(),
            $this->endPosition->getFloorY(),
            $this->endPosition->getFloorZ()
        );
        $this->unvisitedNodes->addSorted($startNode);

        $this->visitNodes();

        if($this->endNode->getParent() != null || $startNode->equals($this->endNode)){
            $this->path = new BlockPath($this->endNode);
        }
        else
        {
            // this looks through the provided options and checks if an ability of the pathfinder is deactivated,
            // if so it activates it and reruns the pathfinding. if there are no other options available, it returns
            if($this->retryCount >= $this->maxRetry) {
                $this->failure = "Max. retry count reached! Can't find path for this target.";
                return;
            }
            $this->retry();
        }

        $this->pathfindingEndNano = microtime(true);
    }

    private function visitNodes(): void
    {
        while(true)
        {
            if($this->unvisitedNodes->getSize() == 0)
            {
                // no unvisited nodes left, nowhere else to go ...
                $this->failure = "No unvisited nodes left";
                break;
            }

            if(count($this->visitedNodes) >= $this->maxNodeVisits)
            {
                // reached limit of nodes to search
                $this->failure = "Number of nodes visited exceeds maximum";
                break;
            } else {
                print_r('Visited Nodes > ' . count($this->visitedNodes) . "\n");
            }

            $nodeToVisit = $this->unvisitedNodes->getAndRemoveFirst();
            if(is_null($nodeToVisit)) {
                $this->failure = "The node to visit is null!";
                break;
            }
			$this->visitedNodes[] = $nodeToVisit;

			// pathing reached end node
			if($this->isTargetReached($nodeToVisit))
            {
                $this->endNode = $nodeToVisit;
                break;
            }

            $this->visitNode($nodeToVisit);
		}
    }

    private function isTargetReached(?BlockPathNode $nodeToVisit): bool
    {
        return $nodeToVisit->equals($this->endNode);
    }

    private function visitNode(?BlockPathNode $node): void
    {
        $this->lookForWalkableNodes($node);

        if($this->useLadders)
            $this->lookForLadderNodes($node);
    }

    private function lookForWalkableNodes(?BlockPathNode $node): void
    {
        for($dX = -1; $dX <= 1; $dX++)
			for($dZ = -1; $dZ <= 1; $dZ++)
				for($dY = -1; $dY <= 1; $dY++)
					$this->validateNodeOffset($node, $dX, $dY, $dZ);
    }

    private function validateNodeOffset(?BlockPathNode $node, int $dX, int $dY, int $dZ): void
    {
        if($dX == 0 && $dY == 0 && $dZ == 0)
            return;

        // prevent diagonal movement if specified
        if(!$this->moveDiagonally && $dX * $dZ != 0)
            return;

        // prevent diagonal movement at the same time as moving up and down
        if($dX * $dZ != 0 && $dY != 0)
            return;

        $newNode = new BlockPathNode($node->x + $dX, $node->y + $dY, $node->z + $dZ);

		if($this->doesNodeAlreadyExist($newNode))
            return;

		// check if player can stand at new node
		if(!$this->isValid($newNode))
            return;

		// check if the diagonal movement is not prevented by blocks to the side
		if($dX * $dZ != 0 && !$this->isDiagonalMovementPossible($node, $dX, $dZ))
            return;

		// check if the player hits his head when going up/down
		if($dY == 1 && !$this->isBlockUnobstructed($node->getPosition($this->startPosition->getWorld())->add(0, 2, 0)))
            return;


		if($dY == -1 && !$this->isBlockUnobstructed($newNode->getPosition($this->startPosition->getWorld())->add(0, 2, 0)))
            return;

		// get transition type (walk up stairs, jump up blocks)
		$transitionType = TransitionType::WALK;
		if($dY == 1)
        {
            $isStair = StairEvaluator::isStair($newNode, $this->startPosition->getWorld());
			if(!$isStair)
                $transitionType = TransitionType::JUMP;
		}


		// calculate weight
		// TODO punish 90° turns
		$sumAbs = abs($dX) + abs($dY) + abs($dZ);
		$weight = 1;
		if($sumAbs == 2)
            $weight = 1.41;
        else if($sumAbs == 3)
            $weight = 1.73;

		// punish jumps to favor stair climbing
		if($transitionType == TransitionType::JUMP)
            $weight += 0.5;

		// actually add the node to the pool
		$newNode->setParent($node, $transitionType, $weight);
		$this->addNode($newNode);
    }

    private function doesNodeAlreadyExist(BlockPathNode $newNode): bool
    {
        if(in_array($newNode, $this->visitedNodes))
            return true;

        return $this->unvisitedNodes->contains($newNode);
    }

    private function isValid(BlockPathNode $newNode): bool
    {
        return $this->canStandAt($newNode->getPosition($this->startPosition->getWorld()));
    }

    private function isDiagonalMovementPossible(?BlockPathNode $node, int $dX, int $dZ): bool
    {
        if(!$this->isUnobstructed((clone $node->getPosition($this->startPosition->getWorld()))->add($dX, 0, 0)))
            return false;
        return $this->isUnobstructed((clone $node->getPosition($this->startPosition->getWorld()))->add(0, 0, $dZ));
    }

    private function isBlockUnobstructed(Vector3 $position): bool
    {
        $block = $this->startPosition->getWorld()->getBlock($position);
        return IdentifierEvaluator::canStandIn($block);
    }

    private function addNode(BlockPathNode $newNode): void
    {
        $newNode->setHeuristicWeight($this->getHeuristicWeight($newNode) * $this->heuristicImportance);
        $this->unvisitedNodes->addSorted($newNode);
    }

    private function lookForLadderNodes(?BlockPathNode $node): void
    {
        $feetPosition = $node->getPosition($this->startPosition->getWorld());

		for($dY = -1; $dY <= 1; $dY++)
		{
            $position = (clone $feetPosition)->add(0, $dY, 0);
            $block = $this->startPosition->getWorld()->getBlock($position);
			if(!$block instanceof Ladder)
                continue;

			$newNode = new BlockPathNode($node->x, $node->y + $dY, $node->z);
			$newNode->setParent($node, TransitionType::CLIMB, self::CLIMBING_EXPENSE);

			if($this->doesNodeAlreadyExist($newNode))
                continue;

			$this->addNode($newNode);
		}
    }

    private function getHeuristicWeight(BlockPathNode $node): float
    {
        return $this->getEuclideanDistance($node);
    }

    private function canStandAt(Position $feetPosition): bool
    {
        if(!$this->isUnobstructed($feetPosition)) {
            return false;
        }
        $block = $this->startPosition->getWorld()->getBlock((clone $feetPosition)->add(0, -1, 0));
        return IdentifierEvaluator::canStandOn($block);
    }

    private function isUnobstructed(Vector3 $feetLocation): bool
    {
        if(!$this->isBlockUnobstructed($feetLocation))
            return false;
        return $this->isBlockUnobstructed((clone$feetLocation)->add(0, 1, 0));
    }

    private function getEuclideanDistance(BlockPathNode $node): float
    {
        $dX = $this->endNode->x - $node->x;
		$dY = $this->endNode->y - $node->y;
		$dZ = $this->endNode->z - $node->z;
		return sqrt($dX * $dX + $dY * $dY+ $dZ * $dZ);
    }

    private function retry(): void
    {
        if($this->canUseDiagonalMovement && !$this->moveDiagonally)
        {
            $this->moveDiagonally = true;
        }

        if($this->canUseLadders && !$this->useLadders)
        {
            $this->useLadders = true;
        }
        $this->retryCount++;
        $this->reset();
        $this->findPath();
    }

    private function reset(): void
    {
        $this->endNode = null;

        $this->unvisitedNodes->clear();
        $this->visitedNodes = [];

        $this->path = null;
        $this->failure = "";
    }
}