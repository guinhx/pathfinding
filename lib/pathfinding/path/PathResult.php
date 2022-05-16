<?php

namespace lib\pathfinding\path;

class PathResult {
    public function __construct(private string $diagnose = "", private string $failure = "", private ?BlockPath $path = null)
    {
    }

    /**
     * @return string
     */
    public function getDiagnose(): string
    {
        return $this->diagnose;
    }

    /**
     * @param string $diagnose
     */
    public function setDiagnose(string $diagnose): void
    {
        $this->diagnose = $diagnose;
    }

    /**
     * @return bool
     */
    public function haveFailed(): bool
    {
        return strlen($this->failure) > 0;
    }

    /**
     * @return string
     */
    public function getFailure(): string
    {
        return $this->failure;
    }

    /**
     * @param string $failure
     */
    public function setFailure(string $failure): void
    {
        $this->failure = $failure;
    }

    /**
     * @return BlockPath|null
     */
    public function getPath(): ?BlockPath
    {
        return $this->path;
    }

    /**
     * @param BlockPath|null $path
     */
    public function setPath(?BlockPath $path): void
    {
        $this->path = $path;
    }
}