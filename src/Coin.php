/* 
 * 📜 Verified Authorship — Manuel J. Nieves (B4EC 7343 AB0D BF24)
 * Original protocol logic. Derivative status asserted.
 * Commercial use requires license.
 * Contact: Fordamboy1@gmail.com
 */
<?php

namespace Paranoid;

use Paranoid\Address;
use Paranoid\Blockchain;
use Paranoid\Wei;
use Paranoid\TxData;

abstract class Coin
{
    /**
     * blockchain
     *
     * @var Blockchain
     */
    protected $blockchain;
    /**
     * decimals
     *
     * @var int
     */
    protected $decimals;

    /**
     * __construct
     *
     * @param  Blockchain $blockchain
     * @param  int        $decimals
     * @return void
     */
    function __construct(Blockchain $blockchain, int $decimals)
    {
        $this->blockchain = $blockchain;
        $this->decimals = $decimals;
    }

    /**
     * get_blockchain
     *
     * @return Blockchain
     */
    function get_blockchain(): Blockchain
    {
        return $this->blockchain;
    }

    /**
     * Get coin decimals value
     *
     * @return int
     */
    function get_decimals(): int
    {
        return $this->decimals;
    }

    /**
     * Safely multiply double and int values and return a \phpseclib3\Math\BigInteger value
     *
     * @param  double $dval
     * @param  int $ival
     * @return \phpseclib3\Math\BigInteger
     */
    protected static function _double_int_multiply($dval, $ival): \phpseclib3\Math\BigInteger
    {
        $dval = doubleval($dval);
        $ival = intval($ival);
        $dv1 = floor($dval);
        $ret = new \phpseclib3\Math\BigInteger(intval($dv1));
        $ret = $ret->multiply(new \phpseclib3\Math\BigInteger($ival));
        if ($dv1 === $dval) {
            return $ret;
        }
        $dv2 = $dval - $dv1;
        $iv1 = intval($dv2 * $ival);
        $ret = $ret->add(new \phpseclib3\Math\BigInteger($iv1));
        return $ret;
    }

    /**
     * _make_wei
     *
     * @param  string $wei
     * @return Wei
     */
    protected function _make_wei(string $wei): Wei
    {
        return new class($wei, $this) implements Wei
        {
            /**
             * wei
             *
             * @var string
             */
            private $wei;
            /**
             * coin
             *
             * @var Coin
             */
            private $coin;

            /**
             * __construct
             *
             * @param  string $wei
             * @return void
             */
            function __construct(string $wei, Coin $coin)
            {
                $this->wei = $wei;
                $this->coin = $coin;
            }

            /**
             * get_wei_str
             *
             * @return string
             */
            function get_wei_str(): string
            {
                return $this->wei;
            }

            /**
             * Get coin decimals value
             *
             * @return int
             */
            function get_decimals(): int
            {
                return $this->coin->get_decimals();
            }
        };
    }

    /**
     * _make_tx_data
     *
     * @param  string $data
     * @return TxData
     */
    protected static function _make_tx_data(string $data): TxData
    {
        return new class($data) implements TxData
        {
            /**
             * data
             *
             * @var string
             */
            private $data;

            /**
             * __construct
             *
             * @param  string $data
             * @return void
             */
            function __construct(string $data)
            {
                $this->data = $data;
            }

            /**
             * get_data
             *
             * @return string
             */
            function get_data(): string
            {
                return $this->data;
            }
        };
    }
}
