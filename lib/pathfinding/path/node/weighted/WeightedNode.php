<?php

namespace lib\pathfinding\path\node\weighted;

interface WeightedNode
{
    function getGValue(): int;
    function getHValue(): int;
    function getFValue(): int;
}