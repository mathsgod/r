<?php

declare(strict_types=1);
error_reporting(E_ALL & ~E_WARNING);

use PHPUnit\Framework\TestCase;

use R\App;

final class ModelTest extends TestCase
{
    public function test_scalar()
    {
        $t = new Testing();
        $t->j = ["a", "b", "c"];
        $t->save();
        $this->assertEquals($t->testing_id, Testing::Scalar("max(testing_id)"));
    }



    //  public function test_first()
    // {
    //        print_r(Testing::First());
    //}

    /*    public function test_first()
    {

        $t = new Testing();
        $t->j = ["a", "b", "c"];
        $t->save();

        $w = [];
        $w[] = ["testing_id=?", $t->testing_id];
        $testing = Testing::First($w);
        $this->assertEquals(["a", "b", "c"], $testing->j);

        $t->delete();
    }*/

    public function testJSON()
    {
        $t = new Testing();
        $t->j = ["a", "b", "c"];
        $t->save();

        $p = new Testing($t->testing_id);

        $this->assertEquals(["a", "b", "c"], $p->j);
        $t->delete();
    }

    public function testCreate()
    {
        $t = new Testing();
        $this->assertInstanceOf(Testing::class, $t);
    }

    public function test_key()
    {
        $key = Testing::_key();
        $this->assertEquals("testing_id", $key);
    }

    public function test_table()
    {
        $table = Testing::_table();
        $this->assertEquals("Testing", $table->name);
    }

    public function test_attribute()
    {
        $attr = Testing::__attribute();
        $this->assertTrue(is_array($attr));
        $this->assertTrue(sizeof($attr) > 0);
    }
}
