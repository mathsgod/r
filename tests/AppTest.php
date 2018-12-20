<?

declare (strict_types = 1);
error_reporting(E_ALL && ~E_WARNING);
use PHPUnit\Framework\TestCase;

use R\App;

final class AppTest extends TestCase
{

    public function testCreate()
    {
        $app = new App();
        $this->assertInstanceOf(App::class, $app);
    }

    public function test_config()
    {
        $app = new App(__DIR__);
        $this->assertTrue(is_array($app->config));
    }


}