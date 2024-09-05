<?php

namespace Paranoid;

interface Wei
{
    /**
     * get_wei_str
     *
     * @return string
     */
    function get_wei_str(): string;

    /**
     * Get coin decimals value
     *
     * @return int
     */
    function get_decimals(): int;
}
