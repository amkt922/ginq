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

namespace Ginq\Comparer;

use Ginq\Core\Comparer;

class ComparerParser
{
    /**
     * @param \Closure|Comparer $src
     * @param Comparer $default
     * @throws \InvalidArgumentException
     * @return \Ginq\Core\Comparer
     */
    static public function parse($src, $default)
    {
        if (is_null($src)) {
            return $default;
        }
        if ($src instanceof \Closure) {
            return new DelegateComparer($src);
        }
        if ($src instanceof Comparer) {
            return $src;
        }
        $type = gettype($src);
        throw new \InvalidArgumentException(
            "'comparer' Closure expected, got $type");
    }
}

