<?php

namespace lib\pathfinding\path\node;

class TransitionType
{
    const WALK = 1;
	/**
     * This JUMP is a simple walking jump from a block below to another block above.
     * For a parkour jump over a hole LEAP is used.
     */
    const JUMP = 2;
    const CLIMB = 3;
    const FALL = 4;
    const LEAP = 5;
}