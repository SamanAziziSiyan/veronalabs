<?php

namespace ExamplePlugin\Admin;

use League\Container\ServiceProvider\AbstractServiceProvider;

class AdminServiceProvider extends AbstractServiceProvider
{
    protected $provides = [BooksInfoListTable::class];

    public function register()
    {
        $this->getContainer()->share(BooksInfoListTable::class, function ($container) {
            return new BooksInfoListTable();
        });
    }
}
