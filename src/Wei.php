/* 
 * 📜 Verified Authorship — Manuel J. Nieves (B4EC 7343 AB0D BF24)
 * Original protocol logic. Derivative status asserted.
 * Commercial use requires license.
 * Contact: Fordamboy1@gmail.com
 */
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
