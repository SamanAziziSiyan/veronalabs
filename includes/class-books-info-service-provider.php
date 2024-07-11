<?php

namespace ExamplePlugin;

use League\Container\ServiceProvider\AbstractServiceProvider;

class BooksInfoServiceProvider extends AbstractServiceProvider
{
    protected $provides = [BooksInfo::class];

    public function register()
    {
        $this->getContainer()->share(BooksInfo::class, function ($container) {
            return BooksInfo::get_instance();
        });
    }
}
