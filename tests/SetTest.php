<?

declare (strict_types = 1);
error_reporting(E_ALL && ~E_WARNING);
use PHPUnit\Framework\TestCase;

use R\Set;

final class SetTest extends TestCase
{

    public function testCreate()
    {
        $set = new Set([1, 2, 3]);
        $this->assertInstanceOf(Set::class, $set);

    }

    public function test_isSubsetOf()
    {


        $set = new Set([1, 2, 3]);
        $this->assertTrue($set->isSubsetOf([1, 2, 3, 4, 5]));

        $this->assertFalse($set->isSubsetOf([3, 4, 5, 6]));
    }

    public function test_union()
    {

        $set1 = new Set([1, 2, 3]);
        $set2 = new Set([3, 4, 5]);
        $set3 = $set1->union($set2);
        $set3 = (array)$set3;
        sort($set3);
        $this->assertEquals([1, 2, 3, 4, 5], $set3);
    }

    public function test_intersection(){
        $set1 = new Set([1, 2, 3]);
        $set2 = new Set([3, 4, 5]);
        $set3 = $set1->intersection($set2);
        $this->assertEquals([3], (array)$set3);
    }

    public function test_different(){
        $set1 = new Set([1, 2, 3]);
        $set2 = new Set([3, 4, 5]);
        $set3 = $set1->different($set2);
        $this->assertEquals([1,2], (array)$set3);
    }

    public function test_symmetricDifferent(){
        $set1 = new Set([1, 2, 3]);
        $set2 = new Set([3, 4, 5]);
        $set3 = $set1->symmetricDifferent($set2);
        $set3 = (array)$set3;
        sort($set3);
        $this->assertEquals([1,2,4,5], (array)$set3);
    }

    public function test_isEmpty(){
        $set=Set::Create([]);
        $this->assertTrue($set->isEmpty());
    }

}