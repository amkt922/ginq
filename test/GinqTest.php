<?php
use Ginq\OrderedGinq;

require_once dirname(dirname(__FILE__)) . "/src/Ginq.php";

class Person
{
    public $id;
    public $name;
    public $city;
    public function __construct($id, $name, $city)
    {
        $this->id = $id;
        $this->name = $name;
        $this->city = $city;
    }
}

/**
 * Test class for Ginq.
 */
class GinqTest extends PHPUnit_Framework_TestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite("GinqTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        //Ginq::useIterator();
        //Ginq::useGenerator();
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
    }

    /**
     * testGetIterator().
     */
    public function testGetIterator()
    {
        $iter = Ginq::from(array(1,2,3,4,5))->getIterator();
        $this->assertTrue($iter instanceof Iterator);
        $arr = array();
        foreach ($iter as $x) {
            $arr[] = $x;
        }
        $this->assertEquals(array(1,2,3,4,5), $arr);
    }

    /**
     * testToAList().
     */
    public function testToAList()
    {
        $expected = array(
            array(0, 1),
            array(1, 2),
            array(2, 3),
            array(3, 4),
            array(4, 5)
        );
        $actual = Ginq::from(array(1,2,3,4,5))->toAList();
        $this->assertEquals($expected, $actual);

        $expected = array(
            array(0, 2),
            array(1, 3),
            array(4, 4),
            array(9, 5),
            array(16, 6)
        );
        $actual = Ginq::from(array(1,2,3,4,5))->select(
            function($v, $k) { return $v+1; },
            function($v, $k) { return $k*$k; }
        )->toAList();
        $this->assertEquals($expected, $actual);
    }

    /**
     * testToAListRec().
     */
    public function testToAListRec()
    {
        $expected = array(
            array(0,
                array(
                    array(0, 1),
                    array(1, 2),
                    array(2, 3)
                )
            ),
            array(1,
                array(
                    array(0, 4),
                    array(1, 5),
                    array(2, 6)
                )
            ),
            array(2,
                array(
                    array(0, 7),
                    array(1, 8),
                    array(2, 9)
                )
            )
        );
        $actual = Ginq::from(array(
            new ArrayIterator(array(1,2,3)),
            new ArrayObject(array(4,5,6)),
            Ginq::from(array(7,8,9))
        ))->toAListRec();
        $this->assertEquals($expected, $actual);
    }

    /**
     * testToArray().
     */
    public function testToArray()
    {
        $data = array(
             array('id' => 1, 'name' => 'Taro',    'city' => 'Takatsuki')
            ,array('id' => 2, 'name' => 'Atsushi', 'city' => 'Ibaraki')
            ,array('id' => 3, 'name' => 'Junko',   'city' => 'Sakai')
        );
        $actual = Ginq::from($data)->select(
            function($x, $k) { return $x; },
            function($x, $k) { return $x['name']; }
        )->toArray();
        $this->assertEquals(
            $actual,
            array(
                'Taro' =>
                    array('id' => 1, 'name' => 'Taro', 'city' => 'Takatsuki'),
                'Atsushi' =>
                    array('id' => 2, 'name' => 'Atsushi', 'city' => 'Ibaraki'),
                'Junko' =>
                    array('id' => 3, 'name' => 'Junko', 'city' => 'Sakai')
            )
        );
        
        // key and value
        $dict = Ginq::from($data)->select(
            function($x, $k) { return "{$x['city']}"; },
            'name' // it means `function($x, $k) { return $x['name']; }`
        )->toArray();
        $this->assertEquals(
            array(
                'Taro' => "Takatsuki",
                'Atsushi' => "Ibaraki",
                'Junko' => "Sakai"
            ), $dict
        );

        // key conflict
        $data = array('apple' => array(1), 'orange' => array(2), 'grape' => array(3));
        $expected = array('apple' => array(1,1,1), 'orange' => array(2,2,2), 'grape' => array(3,3,3));
        $actual = Ginq::cycle($data)->take(9)
            ->toArray(
                function($exist, $v, $k) {
                    return array_merge($exist, $v);
                }
            );
        $this->assertEquals($expected, $actual);
    }

    /**
     * testToArratRec().
     */
    public function testToArrayRec()
    {
        $expected = array(
            0 => array(0 => 1, 1 => 2, 2 => 3),
            1 => array(0 => 4, 1 => 5, 2 => 6),
            2 => array(0 => 7, 1 => 8, 2 => 9)
        );
        $actual = Ginq::from(array(
            new ArrayIterator(array(1,2,3)),
            new ArrayObject(array(4,5,6)),
            Ginq::from(array(7,8,9))
        ))->toArrayRec();
        $this->assertEquals($expected, $actual);
    }

    /**
     * testAny().
     */
    public function testAny()
    {
        $this->assertTrue(
            Ginq::from(array(1,2,3,4,5,6,7,8,9,10))
                ->any(function($x, $k) { return 5 <= $x; })
        );

        $this->assertFalse(
            Ginq::from(array(1,2,3,4,5,6,7,8,9,10))
                ->any(function($x, $k) { return 100 <= $x; })
        );

        $this->assertTrue(
            Ginq::from(array('foo'=>18, 'bar'=>42, 'baz'=> 7))
                ->any(function($x, $k) { return 'bar' == $k; })
        );

        // infinite sequence
        $this->assertTrue(
            Ginq::range(1)->any(function($x, $k) { return 5 <= $x; })
        );
    }

    /**
     * testAll().
     */
    public function testAll()
    {
        $this->assertTrue(
            Ginq::from(array(2,4,6,8,10))
                ->all(function($x, $k) { return $x % 2 == 0; })
        );

        $this->assertFalse(
            Ginq::from(array(1,2,3,4,5,6,7,8,9,10))
                ->all(function($x, $k) { return $x < 10; })
        );

        // infinite sequence
        $this->assertFalse(
            Ginq::range(1)->all(function($x, $k) { return $x <= 10; })
        );
    }

    /**
     * testCount().
     */
    public function testCount()
    {
        $actual = Ginq::from(array(1,2,3,4,5,6,7,8,9,10))->count();
        $this->assertEquals(10, $actual);

        $actual = Ginq::from(array(1,2,3,4,5,6,7,8,9,10))
                    ->count(function($x) { return $x % 2 == 0; });
        $this->assertEquals(5, $actual);

        $actual = Ginq::from(array(1,2,3,4,5,6,7,8,9,10))
                    ->count(function($v, $k) { return $k != 0; });
        $this->assertEquals(9, $actual);
    }

    /**
     * testSum().
     */
    public function testSum()
    {
        $actual = Ginq::from(array(1,2,3,4,5,6,7,8,9,10))->sum();
        $this->assertEquals(55, $actual);

        $actual = Ginq::from(array("apple", "orange", "grape"))
                    ->sum(function($x) { return strlen($x); });
        $this->assertEquals(16, $actual);

        $actual = Ginq::from(array(1,2,3,4,5,6,7,8,9,10))
                    ->sum(function($v, $k) { return $k; });
        $this->assertEquals(45, $actual);
    }

    /**
     * testAverage().
     */
    public function testAverage()
    {
        $actual = Ginq::from(array(1,2,3,4,5,6,7,8,9,10))->average();
        $this->assertEquals(5.5, $actual);

        $actual = Ginq::from(array("apple", "orange", "grape"))
            ->average(function($x) { return strlen($x); });
        $this->assertEquals(16/3, $actual);

        $actual = Ginq::from(array(1,2,3,4,5,6,7,8,9,10))
            ->average(function($v, $k) { return $k; });
        $this->assertEquals(4.5, $actual);
    }

    /**
     * testMin().
     */
    public function testMin()
    {
        $actual = Ginq::from(array(4,2,7,9,1,3,6,5,8))->min();
        $this->assertEquals(1, $actual);

        $data = array(
            array('name'=>'Abe Shinji',     'score'=> 2990),
            array('name'=>'Suzuki Taro',    'score'=>10200),
            array('name'=>'Yamada Taro',    'score'=>  680),
            array('name'=>'Tamura Akira',   'score'=> 5840),
            array('name'=>'Tanaka Ichiro',  'score'=> 8950),
            array('name'=>'Yamada Rindai',  'score'=> 6680),
            array('name'=>'Suzuka Youichi', 'score'=> 6780),
            array('name'=>'Muraoka Kouhei', 'score'=> 1950),
        );
        $actual = Ginq::from($data)->min('score');
        $this->assertEquals(680, $actual);
    }

    /**
     * testMax().
     */
    public function testMax()
    {
        $actual = Ginq::from(array(4,2,7,9,1,3,6,5,8))->max();
        $this->assertEquals(9, $actual);

        $data = array(
            array('name'=>'Abe Shinji',     'score'=> 2990),
            array('name'=>'Suzuki Taro',    'score'=>10200),
            array('name'=>'Yamada Taro',    'score'=>  680),
            array('name'=>'Tamura Akira',   'score'=> 5840),
            array('name'=>'Tanaka Ichiro',  'score'=> 8950),
            array('name'=>'Yamada Rindai',  'score'=> 6680),
            array('name'=>'Suzuka Youichi', 'score'=> 6780),
            array('name'=>'Muraoka Kouhei', 'score'=> 1950),
        );
        $actual = Ginq::from($data)->max('score');
        $this->assertEquals(10200, $actual);
    }

    /**
     * testFirst().
     */
    public function testFirst()
    {
        $person = array(
            array('id'=>1, 'firstname'=>'Taro',    'lastname'=>'Suzuki'),
            array('id'=>2, 'firstname'=>'Ichiro',  'lastname'=>'Yamada'),
            array('id'=>3, 'firstname'=>'Koich',   'lastname'=>'Takana'),
            array('id'=>4, 'firstname'=>'Takashi', 'lastname'=>'Yamada'),
            array('id'=>5, 'firstname'=>'Hiroshi', 'lastname'=>'Kawaguchi'),
        );

        $yamada = function($x) { return $x['lastname'] == "Yamada"; };
        $kato   = function($x) { return $x['lastname'] == "Kato"; };

        // not found
        try {
            Ginq::zero()->first();
            $this->fail();
        } catch (RuntimeException $e) {
            $this->assertTrue(true);
        }

        // not found (with predicate)
        try {
            Ginq::from($person)->first($kato);
            $this->fail();
        } catch (RuntimeException $e) {
            $this->assertTrue(true);
        }

        // found
        $actual = Ginq::from($person)->first();
        $this->assertEquals(array('id'=>1, 'firstname'=>'Taro',    'lastname'=>'Suzuki'), $actual);

        // fond (with predicate)
        $actual = Ginq::from($person)->first($yamada);
        $this->assertEquals(array('id'=>2, 'firstname'=>'Ichiro',  'lastname'=>'Yamada'), $actual);
    }

    /**
     * testFirstOrElse().
     */
    public function testFirstOrElse()
    {
        $person = array(
            array('id'=>1, 'firstname'=>'Taro',    'lastname'=>'Suzuki'),
            array('id'=>2, 'firstname'=>'Ichiro',  'lastname'=>'Yamada'),
            array('id'=>3, 'firstname'=>'Koich',   'lastname'=>'Takana'),
            array('id'=>4, 'firstname'=>'Takashi', 'lastname'=>'Yamada'),
            array('id'=>5, 'firstname'=>'Hiroshi', 'lastname'=>'Kawaguchi'),
        );

        $yamada = function($x) { return $x['lastname'] == "Yamada"; };
        $kato   = function($x) { return $x['lastname'] == "Kato"; };

        // empty
        $actual = Ginq::zero()->firstOrElse('none');
        $this->assertEquals('none', $actual);

        // not found (with predicate)
        $actual = Ginq::from($person)->firstOrElse('none', $kato);
        $this->assertEquals('none', $actual);

        // found
        $actual = Ginq::from($person)->firstOrElse('none');
        $this->assertEquals(array('id'=>1, 'firstname'=>'Taro', 'lastname'=>'Suzuki'), $actual);

        // fond (with predicate)
        $actual = Ginq::from($person)->firstOrElse('none', $yamada);
        $this->assertEquals(array('id'=>2, 'firstname'=>'Ichiro',  'lastname'=>'Yamada'), $actual);
    }

    /**
     * testLast().
     */
    public function testLast()
    {
        $person = array(
            array('id'=>1, 'firstname'=>'Taro',    'lastname'=>'Suzuki'),
            array('id'=>2, 'firstname'=>'Ichiro',  'lastname'=>'Yamada'),
            array('id'=>3, 'firstname'=>'Koich',   'lastname'=>'Takana'),
            array('id'=>4, 'firstname'=>'Takashi', 'lastname'=>'Yamada'),
            array('id'=>5, 'firstname'=>'Hiroshi', 'lastname'=>'Kawaguchi'),
        );

        $yamada = function($x) { return $x['lastname'] == "Yamada"; };
        $kato   = function($x) { return $x['lastname'] == "Kato"; };

        // not found
        try {
            Ginq::zero()->last();
            $this->fail();
        } catch (RuntimeException $e) {
            $this->assertTrue(true);
        }

        // not found (with predicate)
        try {
            Ginq::from($person)->last($kato);
            $this->fail();
        } catch (RuntimeException $e) {
            $this->assertTrue(true);
        }

        // found
        $actual = Ginq::from($person)->last();
        $this->assertEquals(array('id'=>5, 'firstname'=>'Hiroshi', 'lastname'=>'Kawaguchi'), $actual);

        // fond (with predicate)
        $actual = Ginq::from($person)->last($yamada);
        $this->assertEquals(array('id'=>4, 'firstname'=>'Takashi', 'lastname'=>'Yamada'), $actual);

    }

    /**
     * testLastOrElse().
     */
    public function testLastOrElse()
    {
        $person = array(
            array('id'=>1, 'firstname'=>'Taro',    'lastname'=>'Suzuki'),
            array('id'=>2, 'firstname'=>'Ichiro',  'lastname'=>'Yamada'),
            array('id'=>3, 'firstname'=>'Koich',   'lastname'=>'Takana'),
            array('id'=>4, 'firstname'=>'Takashi', 'lastname'=>'Yamada'),
            array('id'=>5, 'firstname'=>'Hiroshi', 'lastname'=>'Kawaguchi'),
        );

        $yamada = function($x) { return $x['lastname'] == "Yamada"; };
        $kato   = function($x) { return $x['lastname'] == "Kato"; };

        // empty
        $actual = Ginq::zero()->lastOrElse('none');
        $this->assertEquals('none', $actual);

        // not found (with predicate)
        $actual = Ginq::from($person)->lastOrElse('none', $kato);
        $this->assertEquals('none', $actual);

        // found
        $actual = Ginq::from($person)->lastOrElse('none');
        $this->assertEquals(array('id'=>5, 'firstname'=>'Hiroshi', 'lastname'=>'Kawaguchi'), $actual);

        // fond (with predicate)
        $actual = Ginq::from($person)->lastOrElse('none', $yamada);
        $this->assertEquals(array('id'=>4, 'firstname'=>'Takashi', 'lastname'=>'Yamada'), $actual);
    }

    /**
     * testElseIfZero().
     */
    public function testElseIf()
    {
        $actual = Ginq::zero()->elseIfZero(null)->toAList();
        $this->assertEquals(array(array(0, null)), $actual);

        $actual = Ginq::zero()->elseIfZero('')->toAList();
        $this->assertEquals(array(array(0, '')), $actual);

        $actual = Ginq::zero()->elseIfZero(-1)->toAList();
        $this->assertEquals(array(array(0, -1)), $actual);
    }

    /**
     * testContains
     */
    public function testContains()
    {
        $this->assertTrue(
            Ginq::from(array('apple', 'orange', 'grape'))
                ->contains('orange')
        );

        $this->assertFalse(
            Ginq::from(array('apple', 'orange', 'grape'))
                ->contains('meow!')
        );
    }

    /**
     * testContainsKey
     */
    public function testContainsKey()
    {
        $this->assertTrue(
            Ginq::from(array('apple' => 1, 'orange' => 2, 'grape' => 3))
                ->containsKey('orange')
        );

        $this->assertFalse(
            Ginq::from(array('apple' => 1, 'orange' => 2, 'grape' => 3))
                ->containsKey('meow!')
        );
    }


    /**
     * testFold
     */
    public function testFoldLeft()
    {
        $actual = Ginq::range(1, 10)->foldLeft(0, function($acc, $v, $k) {
            return $acc - $v;
        });
        $this->assertEquals(-55, $actual);
    }

    /**
     * testFold
     */
    public function testFoldRight()
    {
        $actual = Ginq::range(1, 10)->foldRight(0, function($acc, $v, $k) {
            return $v - $acc;
        });
        $this->assertEquals(-5, $actual);
    }

    /**
     * testFold
     */
    public function testReduceLeft()
    {
        $actual = Ginq::range(0, 10)->reduceLeft(function($acc, $v, $k) {
            return $acc - $v;
        });
        $this->assertEquals(-55, $actual);
    }

    /**
     * testFold
     */
    public function testReduceRight()
    {
        $actual = Ginq::range(1, 10)->reduceRight(function($acc, $v, $k) {
            return $v - $acc;
        });
        $this->assertEquals(-5, $actual);
    }

    /**
     * testZero().
     */
    public function testZero()
    {
        $arr = Ginq::zero()->toArray();
        $this->assertEquals(array(), $arr);
    }

    /**
     * testRange().
     */
    public function testRange()
    {
        // finite sequence
        $xs = Ginq::range(1,10)->toArray();
        $this->assertEquals(array(1,2,3,4,5,6,7,8,9,10), $xs);

        // finite sequence with step
        $xs = Ginq::range(1,10, 2)->toArray();
        $this->assertEquals(array(1,3,5,7,9), $xs);

        // finite sequence with negative step
        $xs = Ginq::range(0,-9, -1)->toArray();
        $this->assertEquals(array(0,-1,-2,-3,-4,-5,-6,-7,-8,-9), $xs);

        // infinite sequence
        $xs = Ginq::range(1)->take(10)->toArray();
        $this->assertEquals(array(1,2,3,4,5,6,7,8,9,10), $xs);

        // infinite sequence with step
        $xs = Ginq::range(10, null, 5)->take(5)->toArray();
        $this->assertEquals(array(10,15,20,25,30), $xs);

        // infinite sequence with negative step
        $xs = Ginq::range(-10, null, -5)->take(5)->toArray();
        $this->assertEquals(array(-10,-15,-20,-25,-30), $xs);

        // contradict range
        $xs = Ginq::range(1, -10, 1)->toArray();
        $this->assertEquals(array(), $xs);

        $xs = Ginq::range(1, 10, -1)->toArray();
        $this->assertEquals(array(), $xs);
    }

    /**
     * testRepeat().
     */
    public function testRepeat()
    {
        // infinite
        $xs = Ginq::repeat("foo")->take(3)->toArray();
        $this->assertEquals(array("foo","foo","foo"), $xs);

        // finite
        $xs = Ginq::repeat("foo", 3)->toArray();
        $this->assertEquals(array("foo","foo","foo"), $xs);
    }

    /**
     * testCycle().
     */
    public function testCycle()
    {
        $expected = array(
            array(0, 'Mon'),
            array(1, 'Tue'),
            array(2, 'Wed'),
            array(3, 'Thu'),
            array(4, 'Fri'),
            array(5, 'Sat'),
            array(6, 'Sun'),
            array(0, 'Mon'),
            array(1, 'Tue'),
            array(2, 'Wed')
        );
        $data = array('Mon','Tue','Wed','Thu','Fri','Sat','Sun');
        $actual = Ginq::cycle($data)->take(10)->toAList();
        $this->assertEquals($expected, $actual);
    }

    /**
     * testFrom().
     */
    public function testFrom()
    {
        // array
        $arr = Ginq::from(array(1,2,3,4,5))->toArray();
        $this->assertEquals(array(1,2,3,4,5), $arr);

        // Iterator
        $arr = Ginq::from(new ArrayIterator(array(1,2,3,4,5)))->toArray();
        $this->assertEquals(array(1,2,3,4,5), $arr);

        // IteratorAggregate
        $arr = Ginq::from(new ArrayObject(array(1,2,3,4,5)))->toArray();
        $this->assertEquals(array(1,2,3,4,5), $arr);

        // Ginq
        $arr = Ginq::from(Ginq::from(array(1,2,3,4,5)))->toArray();
        $this->assertEquals(array(1,2,3,4,5), $arr);
    }

    /**
     * testRenum().
     */
    public function testRenum()
    {
        $expected = array(2,4,6,8,10,12,14,16,18,20);
        $actual = Ginq::range(1,20)
                    ->where(function($x) { return $x % 2 == 0; })
                    ->renum()
                    ->toArray();
        $this->assertEquals($expected, $actual);
    }

    /**
     * testEach().
     */
    public function testEach()
    {
        $data = array('apple'=>99, 'orange'=>105, 'grape'=>298);

        $sideEffect = array();

        $actual = Ginq::from($data)->each(
            function($v, $k) use (&$sideEffect) {
                array_push($sideEffect, array($k, $v));
                return $v * $v;
            }
        )->toArray();

        $this->assertEquals($data, $actual);

        $expectedSideEffect = array(
            array('apple', 99),
            array('orange', 105),
            array('grape', 298)
        );
        $this->assertEquals($expectedSideEffect, $sideEffect);
    }

    /**
     * testSelect().
     */
    public function testSelect()
    {
        // selector function
        $xs = Ginq::from(array(1,2,3,4,5))
                   ->select(function($x, $k) { return $x * $x; })
                   ->toArray();
        $this->assertEquals(array(1,4,9,16,25), $xs);

        // key selector string
        $data = array(
             array('id' => 1, 'name' => 'Taro',    'city' => 'Takatsuki')
            ,array('id' => 2, 'name' => 'Atsushi', 'city' => 'Ibaraki')
            ,array('id' => 3, 'name' => 'Junko',   'city' => 'Sakai')
        );
        $xs = Ginq::from($data)->select("name")->toArray();
        $this->assertEquals(array('Taro','Atsushi','Junko'), $xs);

        // field selector string
        $data = array(
             new Person(1, 'Taro',    'Takatsuki')
            ,new Person(2, 'Atsushi', 'Ibaraki')
            ,new Person(3, 'Junko',   'Sakai')
        );
        $xs = Ginq::from($data)->select("name")->toArray();
        $this->assertEquals(array('Taro','Atsushi','Junko'), $xs);

        // key mapping
        $xs = Ginq::from(array(1,2,3,4,5))
            ->select(null, function($v, $k) { return $k * $k; })
            ->toArray();
        $this->assertEquals(array(0=>1,1=>2,4=>3,9=>4,16=>5), $xs);

        // invalid selector
        try {
            Ginq::from(array(1,2,3,4,5))->select(8);
            $this->fail();
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * testWhere().
     */
    public function testWhere()
    {
        $xs = Ginq::from(array(1,2,3,4,5,6,7,8,9,10))
            ->where(function($x, $k) { return ($x % 2) == 0; })
            ->toArray();
        $this->assertEquals(array(1=>2,3=>4,5=>6,7=>8,9=>10), $xs);
    }

    /**
     * testReverse().
     */
    public function testReverse()
    {
        // reverse iterator
        $xs = Ginq::from(array(1,2,3,4,5))->reverse();

        // to array
        $expected = array(1,2,3,4,5);
        $actual = $xs->toArray();
        $this->assertEquals($expected, $actual);

        // with sequence
        $expected = array(5,4,3,2,1);
        $actual = $xs->renum()->toArray();
        $this->assertEquals($expected, $actual);

        // to assoc
        $expected = array(array(4, 5),array(3, 4),array(2, 3),array(1, 2),array(0, 1));
        $actual = $xs->toAList();
        $this->assertEquals($expected, $actual);
    }

    /**
     * testTake().
     */
    public function testTake()
    {
        $xs = Ginq::from(array(1,2,3,4,5,6,7,8,9))->take(5)->toArray();
        $this->assertEquals(array(1,2,3,4,5), $xs);
    }

    /**
     * testDrop().
     */
    public function testDrop()
    {
        $xs = Ginq::from(array(1,2,3,4,5,6,7,8,9))->drop(5)->toArray();
        $this->assertEquals(array(5=>6,6=>7,7=>8,8=>9), $xs);
    }

    /**
     * testTakeWhile().
     */
    public function testTakeWhile()
    {
        $xs = Ginq::from(array(1,2,3,4,5,6,7,8,9,8,7,6,5,4,3,2,1))
            ->takeWhile(function($x, $k) { return $x <= 5; })
            ->toArray();
        $this->assertEquals(array(1,2,3,4,5), $xs);
    }

    /**
     * testDropWhile().
     */
    public function testDropWhile()
    {
        $xs = Ginq::from(array(1,2,3,4,5,6,7,8,9,8,7,6,5,4,3,2,1))
            ->dropWhile(function($x, $k) { return $x <= 5; })
            ->toArray();
        $this->assertEquals(array(
            5=>6, 6=>7, 7=>8, 8=>9, 9=>8, 10=>7,
            11=>6, 12=>5, 13=>4, 14=>3, 15=>2,16=>1
        ), $xs);
    }

     /**
     * testConcat().
     */
    public function testConcat()
    {
        $expected = array(
            array(0, 1),
            array(1, 2),
            array(2, 3),
            array(3, 4),
            array(4, 5),
            array(0, 6),
            array(1, 7),
            array(2, 8),
            array(3, 9)
        );
        $actual = Ginq::from(array(1,2,3,4,5))->concat(array(6,7,8,9))->toAList();
        $this->assertEquals($expected, $actual);

        $expected = array(array(0, 2),array(1, 4),array(2, 6));

        $actual = \Ginq::from(array())->concat(array(2,4,6))->toAList();
        $this->assertEquals($expected, $actual);

        $actual = \Ginq::from(array(2,4,6))->concat(array())->toAList();
        $this->assertEquals($expected, $actual);
    }

    /**
     * testSelectMany().
     */
    public function testSelectMany()
    {
        $phoneBook = array(
            array(
                'name'   => 'Taro',
                'phones' => array(
                    '03-1234-5678',
                    '090-8421-9061'
                )
            ),
            array(
                'name'   => 'Atsushi',
                'phones' => array(
                    '050-1198-4458'
                )
            ),
            array(
                'name'   => 'Junko',
                'phones' => array(
                    '06-1111-3333',
                    '090-9898-1314',
                    '050-6667-2231'
                )
            )
         );

        $phones = Ginq::from($phoneBook)->selectMany('phones')->toAList();
        $this->assertEquals(array(
            array(0, '03-1234-5678'),
            array(1, '090-8421-9061'),
            array(0, '050-1198-4458'),
            array(0, '06-1111-3333'),
            array(1, '090-9898-1314'),
            array(2, '050-6667-2231')
        ), $phones);
    }

    /**
     * testSelectManyWith().
     */
    public function testSelectManyWithJoin() {

        $phoneBook = array(
            array(
                'name'   => 'Taro',
                'phones' => array(
                    '03-1234-5678',
                    '090-8421-9061'
                )
            ),
            array(
                'name'   => 'Atsushi',
                'phones' => array(
                    '050-1198-4458'
                )
            ),
            array(
                'name'   => 'Junko',
                'phones' => array(
                    '06-1111-3333',
                    '090-9898-1314',
                    '050-6667-2231'
                )
            )
        );
        
        // without key join selector
        $phones = Ginq::from($phoneBook)
            ->selectMany(
                'phones',
                function($v0, $v1, $k0, $k1) {
                    return "{$v0['name']} : $v1";
                }
            )->toAList();
        $this->assertEquals(array(
            array(0,'Taro : 03-1234-5678'),
            array(1,'Taro : 090-8421-9061'),
            array(0,'Atsushi : 050-1198-4458'),
            array(0,'Junko : 06-1111-3333'),
            array(1,'Junko : 090-9898-1314'),
            array(2,'Junko : 050-6667-2231')
        ), $phones);


        $phones = Ginq::from($phoneBook)
            ->selectMany(
                'phones',
                function($v0, $v1, $k0, $k1) {
                    return "$v1";
                },
                function($v0, $v1, $k0, $k1) {
                    return "{$v0['name']}-$k1";
                }
            )->toAList();
        $this->assertEquals(array(
            array('Taro-0',    '03-1234-5678'),
            array('Taro-1',    '090-8421-9061'),
            array('Atsushi-0', '050-1198-4458'),
            array('Junko-0',   '06-1111-3333'),
            array('Junko-1',   '090-9898-1314'),
            array('Junko-2',   '050-6667-2231')
        ), $phones);
    }

    /**
     * testJoin().
     */
    public function testJoin()
    {
        $persons = array(
             array('id' => 1, 'name' => 'Taro')
            ,array('id' => 2, 'name' => 'Atsushi')
            ,array('id' => 3, 'name' => 'Junko')
        );

        $phones = array(
             array('id' => 1, 'owner' => 1, 'phone' => '03-1234-5678')
            ,array('id' => 2, 'owner' => 1, 'phone' => '090-8421-9061')
            ,array('id' => 3, 'owner' => 2, 'phone' => '050-1198-4458')
            ,array('id' => 4, 'owner' => 3, 'phone' => '06-1111-3333')
            ,array('id' => 5, 'owner' => 3, 'phone' => '090-9898-1314')
            ,array('id' => 6, 'owner' => 3, 'phone' => '050-6667-2231')
        );

        // key selector string
        $xs = Ginq::from($persons)->join($phones,
            'id', 'owner',
            function($outer, $inner, $outerKey, $innerKey) {
                return array($outer['name'], $inner['phone']);
            }
        )->toList();
        $this->assertEquals(
            array(
                 array('Taro', '03-1234-5678')
                ,array('Taro', '090-8421-9061')
                ,array('Atsushi', '050-1198-4458')
                ,array('Junko', '06-1111-3333')
                ,array('Junko', '090-9898-1314')
                ,array('Junko', '050-6667-2231')
            ), $xs
        );

        // key selector function
        $xs = Ginq::from($persons)->join($phones,
            function($outer, $k) { return $outer['id']; },
            function($inner, $k) { return $inner['owner']; },
            function($outer, $inner, $outerKey, $innerKey) {
                return array($outer['name'], $inner['phone']);
            }
        )->renum()->toArray();

        $this->assertEquals(
            array(
                 array('Taro', '03-1234-5678')
                ,array('Taro', '090-8421-9061')
                ,array('Atsushi', '050-1198-4458')
                ,array('Junko', '06-1111-3333')
                ,array('Junko', '090-9898-1314')
                ,array('Junko', '050-6667-2231')
            ), $xs
        );
    }

    /**
     * testGroupJoin().
     */
    public function testGroupJoin()
    {
        $persons = array(
             array('id' => 1, 'name' => 'Taro')
            ,array('id' => 2, 'name' => 'Atsushi')
            ,array('id' => 3, 'name' => 'Junko')
            ,array('id' => 4, 'name' => 'Hiroshi')
        );

        $phones = array(
             array('id' => 1, 'owner' => 1, 'phone' => '03-1234-5678')
            ,array('id' => 2, 'owner' => 1, 'phone' => '090-8421-9061')
            ,array('id' => 3, 'owner' => 2, 'phone' => '050-1198-4458')
            ,array('id' => 4, 'owner' => 3, 'phone' => '06-1111-3333')
            ,array('id' => 5, 'owner' => 3, 'phone' => '090-9898-1314')
            ,array('id' => 6, 'owner' => 3, 'phone' => '050-6667-2231')
        );

        $actual = Ginq::from($persons)->groupJoin($phones,
            'id', 'owner',
            function($person, $phones, $outerKey, $innerKey) {
                /* @var OrderedGinq $phones */
                return array($person['name'], $phones->count());
            }
        )->toList();
        $this->assertEquals(
             array(
                 array('Taro',    2)
                ,array('Atsushi', 1)
                ,array('Junko',   3)
                ,array('Hiroshi', 0)
            ), $actual
        );

        // left outer join
        $actual = Ginq::from($persons)->groupJoin($phones,
            'id', 'owner',
            function($person, $phones, $outerKey, $innerKey) {
                /* @var OrderedGinq $phones */
                return array('name'=>$person['name'], 'phones'=>$phones->elseIfZero(null));
            }
        )
        ->selectMany(
            function($person) { return $person['phones'];},
            function($person, $phone) {
                return array($person['name'], $phone['phone']);
            }
        )
        ->toList()
        ;
        $this->assertEquals(
            array(
                 array('Taro',    '03-1234-5678')
                ,array('Taro',    '090-8421-9061')
                ,array('Atsushi', '050-1198-4458')
                ,array('Junko',   '06-1111-3333')
                ,array('Junko',   '090-9898-1314')
                ,array('Junko',   '050-6667-2231')
                ,array('Hiroshi', null)
            ),
            $actual
        );
    }

    /**
     * testZip().
     */
    public function testZip()
    {
        // without key selector
        $xs = Ginq::cycle(array("red", "green"))->zip(Ginq::range(1, 8),
            function($v0, $v1, $k0, $k1) { return "$v1 - $v0"; }
        )->toAList();
        $this->assertEquals(array(
            array(0, "1 - red"),
            array(1, "2 - green"),
            array(2, "3 - red"),
            array(3, "4 - green"),
            array(4, "5 - red"),
            array(5, "6 - green"),
            array(6, "7 - red"),
            array(7, "8 - green")
        ), $xs);
    }

    /**
     * testGroupBy().
     */
    public function testGroupBy()
    {
        $phones = array(
             array('id' => 1, 'owner' => 1, 'phone' => '03-1234-5678')
            ,array('id' => 2, 'owner' => 1, 'phone' => '090-8421-9061')
            ,array('id' => 3, 'owner' => 2, 'phone' => '050-1198-4458')
            ,array('id' => 4, 'owner' => 3, 'phone' => '06-1111-3333')
            ,array('id' => 5, 'owner' => 3, 'phone' => '090-9898-1314')
            ,array('id' => 6, 'owner' => 3, 'phone' => '050-6667-2231')
        );

        $xss = Ginq::from($phones)->groupBy(
            function($x, $k) { return $x['owner']; },
            function($x, $k) { return $x['phone']; }
        )->toArrayRec();

        $this->assertEquals(array(
            1 => array('03-1234-5678', '090-8421-9061'),
            2 => array('050-1198-4458'),
            3 => array('06-1111-3333', '090-9898-1314', '050-6667-2231')
        ), $xss);

        $xss = Ginq::from($phones)
            ->groupBy('owner', 'phone')
            ->toArrayRec();
        $this->assertEquals(array(
            1 => array('03-1234-5678', '090-8421-9061'),
            2 => array('050-1198-4458'),
            3 => array('06-1111-3333', '090-9898-1314', '050-6667-2231')
        ), $xss);

        $count = function ($acc, $x) { return $acc + 1; };

        $xss = Ginq::from($phones)
                ->groupBy('owner')
                ->select(function($gr) use ($count) {
                    return $gr->foldLeft(0, $count);
                })->toArray();
        $this->assertEquals(array(
            1 => 2,
            2 => 1,
            3 => 3
        ), $xss);

        // array key
        $movies = array(
            array('id'=>1, 'title'=>'A Clockwork Orange',
                    'director' => array('id'=>1, 'name'=>'Stanley Kubrick')),
            array('id'=>2, 'title'=>'The Terminator',
                    'director' => array('id'=>3, 'name'=>'James Cameron')),
            array('id'=>3, 'title'=>'Apocalypse Now',
                    'director' => array('id'=>2, 'name'=>'Francis Ford Coppola')),
            array('id'=>4, 'title'=>'Full Metal Jacket',
                    'director' => array('id'=>1, 'name'=>'Stanley Kubrick')),
            array('id'=>5, 'title'=>'The Godfather',
                    'director' => array('id'=>2, 'name'=>'Francis Ford Coppola')),
        );
        $actual = Ginq::from($movies)->groupBy('director')
                ->select(function($gr, $key) {
                    /**
                     * @var Ginq\GroupingGinq $gr
                     */
                    return $gr->foldLeft(array(), function($acc, $x) {
                        $acc[] = $x['title'];
                        return $acc;
                    });
                })->renum()->toArray();
        $this->assertEquals(array(
            array('A Clockwork Orange', 'Full Metal Jacket'),
            array('The Terminator'),
            array('Apocalypse Now', 'The Godfather'),
        ), $actual);

        // object key
        $movies = array(
            new Movie(1, 'A Clockwork Orange',
                new Director(1, 'Stanley Kubrick')),
            new Movie(2, 'The Terminator',
                new Director(3, 'James Cameron')),
            new Movie(3, 'Apocalypse Now',
                new Director(2, 'Francis Ford Coppola')),
            new Movie(4, 'Full Metal Jacket',
                new Director(1, 'Stanley Kubrick')),
            new Movie(5, 'The Godfather',
                new Director(2, 'Francis Ford Coppola')),
        );
        $actual = Ginq::from($movies)->groupBy('director')
            ->select(function($gr, $key) {
                /**
                 * @var Ginq\GroupingGinq $gr
                 */
                return $gr->foldLeft(array(), function($acc, $x) {
                    $acc[] = $x->title;
                    return $acc;
                });
            })->renum()->toArray();
        $this->assertEquals(array(
            array('A Clockwork Orange', 'Full Metal Jacket'),
            array('The Terminator'),
            array('Apocalypse Now', 'The Godfather'),
        ), $actual);
    }

    /**
     * testOrdering
     */
    public function testOrdering()
    {
        $data = array(9,7,8,3,5,2,1,6,4);

        $xs = Ginq::from($data)->orderBy()->renum()->toArray();
        $this->assertEquals(array(1,2,3,4,5,6,7,8,9), $xs);

        $xs = Ginq::from($data)->orderByDesc()->renum()->toArray();
        $this->assertEquals(array(9,8,7,6,5,4,3,2,1), $xs);

        $data = array(
            array('name'=>'Tanaka Ichiro',  'score'=> 8950, 'born'=>1974),
            array('name'=>'Suzuki Taro',    'score'=>10200, 'born'=>1986),
            array('name'=>'Tamura Akira',   'score'=> 5840, 'born'=>1974),
            array('name'=>'Suzuka Youichi', 'score'=> 6780, 'born'=>1990),
            array('name'=>'Abe Shinji',     'score'=> 2990, 'born'=>1969),
            array('name'=>'Muraoka Kouhei', 'score'=> 1950, 'born'=>1978),
            array('name'=>'Yamada Taro',    'score'=>  680, 'born'=>1986),
            array('name'=>'Yamada Rindai',  'score'=> 6680, 'born'=>1974),
        );

        $expected = array(
            array('name'=>'Abe Shinji',     'score'=> 2990, 'born'=>1969),
            array('name'=>'Muraoka Kouhei', 'score'=> 1950, 'born'=>1978),
            array('name'=>'Suzuka Youichi', 'score'=> 6780, 'born'=>1990),
            array('name'=>'Suzuki Taro',    'score'=>10200, 'born'=>1986),
            array('name'=>'Tamura Akira',   'score'=> 5840, 'born'=>1974),
            array('name'=>'Tanaka Ichiro',  'score'=> 8950, 'born'=>1974),
            array('name'=>'Yamada Rindai',  'score'=> 6680, 'born'=>1974),
            array('name'=>'Yamada Taro',    'score'=>  680, 'born'=>1986),
        );
        $xs = Ginq::from($data)->orderBy('name')->renum()->toArray();
        $this->assertEquals($expected, $xs);

        $expected = array(
            array('name'=>'Abe Shinji',     'score'=> 2990, 'born'=>1969),
            array('name'=>'Tamura Akira',   'score'=> 5840, 'born'=>1974),
            array('name'=>'Yamada Rindai',  'score'=> 6680, 'born'=>1974),
            array('name'=>'Tanaka Ichiro',  'score'=> 8950, 'born'=>1974),
            array('name'=>'Muraoka Kouhei', 'score'=> 1950, 'born'=>1978),
            array('name'=>'Yamada Taro',    'score'=>  680, 'born'=>1986),
            array('name'=>'Suzuki Taro',    'score'=>10200, 'born'=>1986),
            array('name'=>'Suzuka Youichi', 'score'=> 6780, 'born'=>1990),
        );
        $xs = Ginq::from($data)
            ->orderBy(function($x) { return $x['born']; })
            ->thenBy('score')
            ->renum()->toArray();
        $this->assertEquals($expected, $xs);

        $xs = Ginq::from($data)->orderBy()->thenBy();

        $expected = array(
            array('name'=>'Abe Shinji',     'score'=> 2990, 'born'=>1969),
            array('name'=>'Tanaka Ichiro',  'score'=> 8950, 'born'=>1974),
            array('name'=>'Yamada Rindai',  'score'=> 6680, 'born'=>1974),
            array('name'=>'Tamura Akira',   'score'=> 5840, 'born'=>1974),
            array('name'=>'Muraoka Kouhei', 'score'=> 1950, 'born'=>1978),
            array('name'=>'Suzuki Taro',    'score'=>10200, 'born'=>1986),
            array('name'=>'Yamada Taro',    'score'=>  680, 'born'=>1986),
            array('name'=>'Suzuka Youichi', 'score'=> 6780, 'born'=>1990),
        );
        $xs = Ginq::from($data)
            ->orderBy('born')
            ->thenByDesc('score')
            ->renum()->toArray();
        $this->assertEquals($expected, $xs);

        $expected = array(
            array('name'=>'Suzuka Youichi', 'score'=> 6780, 'born'=>1990),
            array('name'=>'Suzuki Taro',    'score'=>10200, 'born'=>1986),
            array('name'=>'Yamada Taro',    'score'=>  680, 'born'=>1986),
            array('name'=>'Muraoka Kouhei', 'score'=> 1950, 'born'=>1978),
            array('name'=>'Tanaka Ichiro',  'score'=> 8950, 'born'=>1974),
            array('name'=>'Yamada Rindai',  'score'=> 6680, 'born'=>1974),
            array('name'=>'Tamura Akira',   'score'=> 5840, 'born'=>1974),
            array('name'=>'Abe Shinji',     'score'=> 2990, 'born'=>1969),
        );
        $xs = Ginq::from($data)
            ->orderByDesc('born')
            ->thenByDesc('score')
            ->renum()->toArray();
        $this->assertEquals($expected, $xs);

        // custom comparer
        $expected = array(
            array('name'=>'Abe Shinji',     'score'=> 2990, 'born'=>1969),
            array('name'=>'Suzuki Taro',    'score'=>10200, 'born'=>1986),
            array('name'=>'Yamada Taro',    'score'=>  680, 'born'=>1986),
            array('name'=>'Tamura Akira',   'score'=> 5840, 'born'=>1974),
            array('name'=>'Tanaka Ichiro',  'score'=> 8950, 'born'=>1974),
            array('name'=>'Yamada Rindai',  'score'=> 6680, 'born'=>1974),
            array('name'=>'Suzuka Youichi', 'score'=> 6780, 'born'=>1990),
            array('name'=>'Muraoka Kouhei', 'score'=> 1950, 'born'=>1978),
        );
        $cmp = function ($x, $y) {
            if ($x === $y) return 0;
            return ($x < $y) ? -1 : 1;
        };
        $xs = Ginq::from($data)
            ->orderBy(function($v, $k) { return strlen($v['name']); }, $cmp)
            ->thenByDesc(function($v, $k) { return $v['score']; }, $cmp)
            ->renum()->toArray();
        $this->assertEquals($expected, $xs);
    }

    /**
     * testDistinct().
     */
    public function testDistinct()
    {
        $xs = Ginq::from(array(5, 7, 7, 8, 2, 1, 7, 5))
                ->distinct()->renum()->toArray();
        $this->assertEquals(array(5, 7, 8, 2, 1), $xs);
    }

    /**
     * testUnion().
     */
    public function testUnion()
    {
        $xs = Ginq::from(array(1,2,3,4,5))->union(array(3,4,5,6,7))->toList();
        $this->assertEquals(array(1,2,3,4,5,6,7), $xs);

        $xs = Ginq::from(array(1,2,3,4,5))->union(array(3,4,5))->toList();
        $this->assertEquals(array(1,2,3,4,5), $xs);

        $xs = Ginq::from(array(1,2,3,4,5))->union(array(1,3,5))->toList();
        $this->assertEquals(array(1,2,3,4,5), $xs);
    }

    /**
     * testIntersect().
     */
    public function testIntersect()
    {
        $xs = Ginq::from(array(1,2,3,4,5))->intersect(array(3,4,5,6,7))->toList();
        $this->assertEquals(array(3,4,5), $xs);

        $xs = Ginq::from(array(1,2,3,4,5))->intersect(array(3,4,5))->toList();
        $this->assertEquals(array(3,4,5), $xs);

        $xs = Ginq::from(array(1,2,3,4,5))->intersect(array(1,3,5,9))->toList();
        $this->assertEquals(array(1,3,5), $xs);
    }

    /**
     * testExcept().
     */
    public function testExcept()
    {
        $xs = Ginq::from(array(1,2,3,4,5))->except(array(3,4,5,6,7))->toList();
        $this->assertEquals(array(1, 2), $xs);

        $xs = Ginq::from(array(1,2,3,4,5))->except(array(3,4,5))->toList();
        $this->assertEquals(array(1, 2), $xs);

        $xs = Ginq::from(array(1,2,3,4,5))->except(array(1,3,5,9))->toList();
        $this->assertEquals(array(2, 4), $xs);
    }

    /**
     * testSequenceEquals().
     */
    public function testSequenceEquals()
    {
        $actual = Ginq::from(array(1,2,3,4,5))->sequenceEquals(array(1,2,3,4,5));
        $this->assertEquals(true, $actual);

        $actual = Ginq::from(array(1,2,3,4,5))->sequenceEquals(array(6,7,8,9));
        $this->assertEquals(false, $actual);

        $actual = Ginq::from(array(1,2,3,4,5))->sequenceEquals(array(4,5,6,7));
        $this->assertEquals(false, $actual);

        $apple  = array('id'=>1, 'name'=>'apple');
        $orange = array('id'=>2, 'name'=>'orange');
        $grape  = array('id'=>3, 'name'=>'grape');
        $banana = array('id'=>4, 'name'=>'banana');

        $actual = Ginq::from(array($apple, $orange, $grape))->sequenceEquals(array($apple, $orange, $grape));
        $this->assertEquals(true, $actual);

        $actual = Ginq::from(array($apple, $orange, $grape))->sequenceEquals(array($orange, $grape, $banana));
        $this->assertEquals(false, $actual);

        $lhs = Ginq::from(array($apple, $orange, $grape))->select(function($x){return $x;});
        $rhs = Ginq::from(array($apple, $orange, $grape))->select(function($x){return $x;});
        $actual = $lhs->sequenceEquals($rhs);
        $this->assertEquals(true, $actual);

        $lhs = Ginq::from(array($apple, $orange, $grape))->select(function($x){return $x;});
        $rhs = Ginq::from(array($orange, $grape, $banana))->select(function($x){return $x;});
        $actual = $lhs->sequenceEquals($rhs);
        $this->assertEquals(false, $actual);
    }

    /**
     * testMemoize().
     */
    public function testMemoize()
    {
        $xs = Ginq::from(array(1,2,3,4,5,6,7,8,9,10))
                ->where(function($x) { return $x % 2 == 0; })
                ->renum()
                ->memoize();

        $arr0 = $xs->take(2)->toArray();
        $this->assertEquals(array(2,4), $arr0);

        $arr1 = $xs->toArray();
        $this->assertEquals(array(2,4,6,8,10), $arr1);

        // empty iterator
        $zero = Ginq::zero()->memoize()->toArray();
        $this->assertEquals(array(), $zero);

        // empty iterator
        $zero = Ginq::from(array())->memoize()->toArray();
        $this->assertEquals(array(), $zero);
    }

 }

class Movie
{
    public $id;
    public $title;
    public function __construct($id, $title, $director)
    {
        $this->id       = $id;
        $this->title    = $title;
        $this->director = $director;
    }
}

class Director
{
    public $id;
    public $name;
    public function __construct($id, $name)
    {
        $this->id   = $id;
        $this->name = $name;
    }
}

// Call GinqTest::main() if this source file is executed directly.
if (defined('PHPUnit_MAIN_METHOD') && PHPUnit_MAIN_METHOD == "GinqTest::main") {
    GinqTest::main();
}

