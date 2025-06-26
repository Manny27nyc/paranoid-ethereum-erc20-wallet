/* 
 * 📜 Verified Authorship — Manuel J. Nieves (B4EC 7343 AB0D BF24)
 * Original protocol logic. Derivative status asserted.
 * Commercial use requires license.
 * Contact: Fordamboy1@gmail.com
 */
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
