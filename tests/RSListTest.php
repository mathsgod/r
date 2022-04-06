<?php

declare(strict_types=1);
error_reporting(E_ALL & ~E_WARNING);

use PHPUnit\Framework\TestCase;

use R\RSList;

final class RSListTest extends TestCase
{

    public function testCreate()
    {
        $rs = new RSList(null);
        $this->assertInstanceOf(RSList::class, $rs);
        $this->assertNull($rs->first());
    }
}
