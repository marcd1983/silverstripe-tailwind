<?php

namespace App\Schema;

interface SchemaProvider
{
    /** Return one or more schema.org nodes as associative arrays */
    public function provideSchema(): array;
}
