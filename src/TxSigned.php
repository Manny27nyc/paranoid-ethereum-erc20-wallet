<?php

namespace Paranoid;

interface TxSigned
{
    /**
     * get_tx_signed_raw
     *
     * @return string
     */
    function get_tx_signed_raw(): string;
}
