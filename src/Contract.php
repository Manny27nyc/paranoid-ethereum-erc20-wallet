<?php

namespace Paranoid;

use Paranoid\Address;

interface Contract
{
    /**
     * get_contract_address
     *
     * @return Address
     */
    function get_contract_address(): Address;
    /**
     * get_abi
     *
     * @return string
     */
    function get_abi(): string;
}
