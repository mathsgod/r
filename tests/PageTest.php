<?php

declare(strict_types=1);
error_reporting(E_ALL && ~E_WARNING);

use PHPUnit\Framework\TestCase;

use R\App;
use R\Page;

require_once(__DIR__ . "/APage.php");

final class PageTest extends TestCase
{

    public function testCreate()
    {
        $app = new App(__DIR__);
        $page = new APage($app);

        $this->assertInstanceOf(APage::class, $page);
    }
}
