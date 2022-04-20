<?php

/**
 * Contao Bootstrap grid.
 *
 * @filesource
 */

declare(strict_types=1);

namespace ContaoBootstrap\Grid;

use ContaoBootstrap\Grid\Definition\Grid;
use Iterator;

use function assert;
use function is_string;

/**
 * GridIterator to iterate over the grid columns.
 */
final class GridIterator implements Iterator
{
    /**
     * Grid.
     */
    private Grid $grid;

    /**
     * Current index.
     */
    private int $index = 0;

    /**
     * @param Grid $grid The grid.
     */
    public function __construct(Grid $grid)
    {
        $this->grid = $grid;
    }

    /**
     * Build a row.
     */
    public function row(): string
    {
        $row = $this->grid->buildRow(true);
        assert(is_string($row));

        return $row;
    }

    /**
     * Get all resets.
     *
     * @return list<string>
     */
    public function resets(): array
    {
        return $this->grid->buildResets($this->index);
    }

    public function current(): string
    {
        $column = $this->grid->buildColumn($this->index, true);
        assert(is_string($column));

        return $column;
    }

    public function next(): void
    {
        $this->index++;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->index;
    }

    public function valid(): bool
    {
        return true;
    }

    public function rewind(): void
    {
        $this->index = 0;
    }
}
