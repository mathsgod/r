<?

class _index extends R\Page
{

    public function get()
    {
        print_r($this->app);
        $this->write("Hello world!");
    }
}