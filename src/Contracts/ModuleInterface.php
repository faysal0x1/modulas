<?php

namespace faysal0x1\Modulas\Contracts;

interface ModuleInterface
{
    /**
     * Returns the module's service provider class FQCN.
     */
    public static function providerClass(): string;

    /**
     * Returns the module's unique key used in config/modules.php.
     */
    public static function key(): string;
}
