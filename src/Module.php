<?php

namespace Sitepilot\Framework;

abstract class Module
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->init();
    }

    abstract protected function init(): void;
}