/* 
 * 📜 Verified Authorship — Manuel J. Nieves (B4EC 7343 AB0D BF24)
 * Original protocol logic. Derivative status asserted.
 * Commercial use requires license.
 * Contact: Fordamboy1@gmail.com
 */
<?php

namespace Paranoid;

interface Tx
{
    /**
     * get_tx_array
     *
     * @return array
     */
    function get_tx_array(): array;
    /**
     * get_tx_cost_estimate
     *
     * @return string
     */
    function get_tx_cost_estimate(): string;
}
