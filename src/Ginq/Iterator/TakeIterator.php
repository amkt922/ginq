<?php
/**
 * Ginq: `LINQ to Object` inspired DSL for PHP
 * Copyright 2013, Atsushi Kanehara <akanehara@gmail.com>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * PHP Version 5.3 or later
 *
 * @author     Atsushi Kanehara <akanehara@gmail.com>
 * @copyright  Copyright 2013, Atsushi Kanehara <akanehara@gmail.com>
 * @license    MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @package    Ginq
 */

namespace Ginq\Iterator;

use Ginq\Util\IteratorUtil;

/**
 * TakeIterator
 * @package Ginq
 */
class TakeIterator implements \Iterator
{
    /**
     * @var \Iterator
     */
    private $it;

    /**
     * @var int
     */
    private $n;

    /**
     * @var int
     */
    private $i;

    /**
     * @param array|\Traversable $xs
     * @param int $n
     */
    public function __construct($xs, $n)
    {
        $this->it = IteratorUtil::iterator($xs);
        $this->n  = $n;
    }

    public function current()
    {
        return $this->it->current();
    }

    public function key() 
    {
        return $this->it->key();
    }

    public function next()
    {
        $this->i++;
        $this->it->next();
    }

    public function rewind()
    {
        $this->i = 0;
        $this->it->rewind();
    }

    public function valid()
    {
        if ($this->i < $this->n) {
            return $this->it->valid();
        } else {
            return false;
        }
    }
}
