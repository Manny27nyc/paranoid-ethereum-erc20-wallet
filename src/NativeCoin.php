<?php

namespace Paranoid;

use Paranoid\Coin;
use Paranoid\Address;
use Paranoid\Blockchain;
use Paranoid\NativeWei;

final class NativeCoin extends Coin
{
    /**
     * __construct
     *
     * @param  Blockchain $blockchain
     * @return void
     */
    function __construct(Blockchain $blockchain)
    {
        parent::__construct($blockchain, $blockchain->get_native_coin_decimals());
    }

    /**
     * get_address_balance
     *
     * @param  Address $address
     * @return NativeWei
     * @throws \Exception
     */
    function get_address_balance(Address $address): NativeWei
    {
        $balance = $this->blockchain->get_address_balance(
            $address
        );

        return $this->blockchain->make_native_wei($balance);
    }

    /**
     * make_amount_to_send
     *
     * @param  Account $account
     * @param  float $amount
     * @return NativeWei
     * @throws \InvalidArgumentException
     */
    function make_amount_to_send(Account $account, float $amount): NativeWei
    {
        $balance = $this->get_account_balance($account);
        $balance_bn = new \phpseclib3\Math\BigInteger($balance->get_wei_str());
        $token_quantity_wei_bn = self::_double_int_multiply($amount, pow(10, $this->decimals));
        $token_quantity_wei_str = $token_quantity_wei_bn->toString();

        if ($balance_bn->compare($token_quantity_wei_bn) < 0) {
            $balance_str = $balance_bn->toString();
            throw new \InvalidArgumentException(sprintf("Insufficient funds: balance_wei(%s) < token_quantity_wei(%s)", $balance_str, $token_quantity_wei_str));
        }

        return $this->blockchain->make_native_wei($token_quantity_wei_str);
    }

    /**
     * make_transaction
     *
     * @param  Account $from
     * @param  Address $to
     * @param  NativeWei $tx_value
     * @return Tx
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    function make_transaction(Account $from, Address $to, NativeWei $tx_value): Tx
    {
        $tx_data = $this->_make_tx_data('');
        return $this->blockchain->make_transaction($from, $to, $tx_data, $tx_value);
    }
}
