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
