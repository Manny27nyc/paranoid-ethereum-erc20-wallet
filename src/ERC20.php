<?php

namespace Paranoid;

use Paranoid\Coin;
use Paranoid\Address;
use Paranoid\Blockchain;
use Paranoid\Wei;
use Paranoid\TxData;
use Paranoid\Contract;

final class ERC20 extends Coin implements Contract
{
    /**
     * The ERC20 smart contract ABI
     *
     * @var string The ERC20 smart contract ABI
     * @see http://www.webtoolkitonline.com/json-minifier.html
     */
    const ERC20_ABI = '[{"inputs":[],"stateMutability":"nonpayable","type":"constructor"},{"inputs":[{"internalType":"address","name":"spender","type":"address"},{"internalType":"uint256","name":"allowance","type":"uint256"},{"internalType":"uint256","name":"needed","type":"uint256"}],"name":"ERC20InsufficientAllowance","type":"error"},{"inputs":[{"internalType":"address","name":"sender","type":"address"},{"internalType":"uint256","name":"balance","type":"uint256"},{"internalType":"uint256","name":"needed","type":"uint256"}],"name":"ERC20InsufficientBalance","type":"error"},{"inputs":[{"internalType":"address","name":"approver","type":"address"}],"name":"ERC20InvalidApprover","type":"error"},{"inputs":[{"internalType":"address","name":"receiver","type":"address"}],"name":"ERC20InvalidReceiver","type":"error"},{"inputs":[{"internalType":"address","name":"sender","type":"address"}],"name":"ERC20InvalidSender","type":"error"},{"inputs":[{"internalType":"address","name":"spender","type":"address"}],"name":"ERC20InvalidSpender","type":"error"},{"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"owner","type":"address"},{"indexed":true,"internalType":"address","name":"spender","type":"address"},{"indexed":false,"internalType":"uint256","name":"value","type":"uint256"}],"name":"Approval","type":"event"},{"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"from","type":"address"},{"indexed":true,"internalType":"address","name":"to","type":"address"},{"indexed":false,"internalType":"uint256","name":"value","type":"uint256"}],"name":"Transfer","type":"event"},{"inputs":[{"internalType":"address","name":"owner","type":"address"},{"internalType":"address","name":"spender","type":"address"}],"name":"allowance","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"stateMutability":"view","type":"function"},{"inputs":[{"internalType":"address","name":"spender","type":"address"},{"internalType":"uint256","name":"value","type":"uint256"}],"name":"approve","outputs":[{"internalType":"bool","name":"","type":"bool"}],"stateMutability":"nonpayable","type":"function"},{"inputs":[{"internalType":"address","name":"account","type":"address"}],"name":"balanceOf","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"stateMutability":"view","type":"function"},{"inputs":[],"name":"decimals","outputs":[{"internalType":"uint8","name":"","type":"uint8"}],"stateMutability":"view","type":"function"},{"inputs":[],"name":"name","outputs":[{"internalType":"string","name":"","type":"string"}],"stateMutability":"view","type":"function"},{"inputs":[],"name":"symbol","outputs":[{"internalType":"string","name":"","type":"string"}],"stateMutability":"view","type":"function"},{"inputs":[],"name":"totalSupply","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"stateMutability":"view","type":"function"},{"inputs":[{"internalType":"address","name":"to","type":"address"},{"internalType":"uint256","name":"value","type":"uint256"}],"name":"transfer","outputs":[{"internalType":"bool","name":"","type":"bool"}],"stateMutability":"nonpayable","type":"function"},{"inputs":[{"internalType":"address","name":"from","type":"address"},{"internalType":"address","name":"to","type":"address"},{"internalType":"uint256","name":"value","type":"uint256"}],"name":"transferFrom","outputs":[{"internalType":"bool","name":"","type":"bool"}],"stateMutability":"nonpayable","type":"function"}]';

    /**
     * contract_address
     *
     * @var Address
     */
    private $contract_address;

    /**
     * __construct
     *
     * @param  Address $contract_address
     * @param  Blockchain $blockchain
     * @return void
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    function __construct(Address $contract_address, Blockchain $blockchain)
    {
        $this->contract_address = $contract_address;
        $this->blockchain = $blockchain;
        if ('0x' === $this->_get_contract_code()) {
            throw new \InvalidArgumentException('Token address is not a contract: ' . $this->contract_address->get_address());
        }
        $this->decimals = $this->_get_decimals_impl();
    }

    /**
     * get_contract_address
     *
     * @return Address
     */
    function get_contract_address(): Address
    {
        return $this->contract_address;
    }

    /**
     * get_abi
     *
     * @return string
     */
    function get_abi(): string
    {
        return self::ERC20_ABI;
    }

    /**
     * Get token decimals value
     *
     * @return int
     */
    function get_decimals(): int
    {
        return $this->decimals;
    }

    /**
     * get_address_balance
     *
     * @param  Address $address
     * @return Wei
     * @throws \Exception
     */
    function get_address_balance(Address $address): Wei
    {
        $ret = null;
        $err = null;
        $callback = function ($error, $result) use (&$ret, &$err) {
            if ($error !== null) {
                $err = $error;
                return;
            }
            foreach ($result as $key => $res) {
                $ret = $res;
                break;
            }
        };

        $this->blockchain->call_contract_method(
            $this,
            "balanceOf",
            [$address->get_address()],
            $callback
        );

        if (!is_null($err)) {
            throw new \Exception('Failed to get token balance: ' . $err);
        }

        if (is_null($ret)) {
            throw new \Exception('Failed to get token balance');
        }

        return $this->_make_wei($ret->toString());
    }

    /**
     * make_transfer_data
     *
     * @param  Address $toAddress
     * @param  Wei $tokens_amount
     * @return TxData
     */
    function make_transfer_data(Address $toAddress, Wei $tokens_amount): TxData
    {
        if ($this->get_decimals() !== $tokens_amount->get_decimals()) {
            throw new \InvalidArgumentException('Wei tokens_amount from another token or blockchain provided');
        }
        $data = $this->blockchain->get_contract_method_data($this, 'transfer', [$toAddress->get_address(), $tokens_amount->get_wei_str()]);
        return self::_make_tx_data($data);
    }

    /**
     * make_transaction
     *
     * @param  Account $from
     * @param  TxData $tx_data
     * @param  NativeWei $tx_value
     * @return Tx
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    function make_transaction(Account $from, TxData $tx_data, NativeWei $tx_value): Tx
    {
        return $this->blockchain->make_transaction($from, $this->contract_address, $tx_data, $tx_value);
    }

    /**
     * make_amount_to_send
     *
     * @param  Account $account
     * @param  float $amount
     * @return Wei
     * @throws \InvalidArgumentException
     */
    function make_amount_to_send(Account $account, float $amount): Wei
    {
        $balance = $this->get_account_balance($account);
        $balance_bn = new \phpseclib3\Math\BigInteger($balance->get_wei_str());
        $token_quantity_wei_bn = self::_double_int_multiply($amount, pow(10, $this->decimals));
        $token_quantity_wei_str = $token_quantity_wei_bn->toString();

        if ($balance_bn->compare($token_quantity_wei_bn) < 0) {
            $balance_str = $balance_bn->toString();
            throw new \InvalidArgumentException(sprintf("Insufficient funds: balance_wei(%s) < token_quantity_wei(%s)", $balance_str, $token_quantity_wei_str));
        }

        return $this->_make_wei($token_quantity_wei_str);
    }

    /**
     * _get_decimals_impl
     *
     * @return int
     * @throws \Exception
     */
    private function _get_decimals_impl(): int
    {
        $ret = null;
        $err = null;
        $callback = function ($error, $result) use (&$ret, &$err) {
            if ($error !== null) {
                $err = $error;
                return;
            }
            foreach ($result as $key => $res) {
                $ret = $res;
                break;
            }
        };
        $this->blockchain->call_contract_method(
            $this,
            "decimals",
            [],
            $callback
        );


        if (!is_null($err)) {
            throw new \Exception('Failed to get token decimals: ' . $err);
        }

        if (is_null($ret)) {
            throw new \Exception('Failed to get token decimals');
        }

        return intval($ret->toString());
    }

    private function _get_contract_code(): string
    {
        return $this->blockchain->get_contract_code($this);
    }
}
