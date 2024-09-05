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
    const ERC20_ABI = '[{"constant":true,"inputs":[],"name":"totalSupply","outputs":[{"name":"supply","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[],"name":"name","outputs":[{"name":"","type":"string"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[],"name":"decimals","outputs":[{"name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[{"name":"_owner","type":"address"}],"name":"balanceOf","outputs":[{"name":"balance","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[{"name":"_owner","type":"address"},{"name":"_spender","type":"address"}],"name":"allowance","outputs":[{"name":"remaining","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"anonymous":false,"inputs":[{"indexed":true,"name":"_owner","type":"address"},{"indexed":true,"name":"_spender","type":"address"},{"indexed":false,"name":"_value","type":"uint256"}],"name":"Approval","type":"event"},{"anonymous":false,"inputs":[{"indexed":true,"name":"_from","type":"address"},{"indexed":true,"name":"_to","type":"address"},{"indexed":false,"name":"_value","type":"uint256"}],"name":"Transfer","type":"event"},{"constant":false,"inputs":[{"name":"_spender","type":"address"},{"name":"_value","type":"uint256"}],"name":"approve","outputs":[{"name":"success","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":false,"inputs":[{"name":"_to","type":"address"},{"name":"_value","type":"uint256"}],"name":"transfer","outputs":[{"name":"success","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":false,"inputs":[{"name":"_from","type":"address"},{"name":"_to","type":"address"},{"name":"_value","type":"uint256"}],"name":"transferFrom","outputs":[{"name":"success","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"}]';

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
        if (empty($this->_get_contract_code())) {
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

        return $this->_make_wei($ret);
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
        $nonce = $this->blockchain->get_account_nonce($from);
        $nonceb = \BitWasp\Buffertools\Buffer::int(intval($nonce));
        $gasLimit = \BitWasp\Buffertools\Buffer::int(intval($this->blockchain->get_option('gas_limit')));

        $data = $tx_data->get_data();
        $value = $tx_value->get_wei_str();
        $value_bn = new \phpseclib3\Math\BigInteger($value);
        $value_hex = $value_bn->toHex();
        $transactionData = [
            'from' => $from->get_address()->get_address(),
            'nonce' => '0x' . $nonceb->getHex(),
            'to' => strtolower($this->contract_address->get_address()),
            'gas' => '0x' . ltrim($gasLimit->getHex(), '0'),
            'value' => '0x' . (empty($value_hex) ? '0' : $value_hex),
            'chainId' => $this->blockchain->get_network_id(),
            'data' => !empty($data) ? '0x' . $data : null,
        ];

        $gasEstimate = new \phpseclib3\Math\BigInteger($this->blockchain->get_gas_estimate($transactionData));
        if ($gasLimit->getHex() === $gasEstimate->toHex()) {
            throw new \Exception("Too low gas_limit option specified: " . $gasLimit->getHex());
        }
        $transactionData['gas'] = '0x' . $gasEstimate->toHex();
        unset($transactionData['from']);

        $gasPrice = $this->blockchain->get_gas_price_wei();
        $gasPrice = \BitWasp\Buffertools\Buffer::int(intval($gasPrice));
        $gasPriceTip = $this->blockchain->get_gas_price_tip_wei();

        if (is_null($gasPriceTip)) {
            // pre-EIP1559
            $transactionData['gasPrice'] = '0x' . $gasPrice->getHex();
        } else {
            $transactionData['accessList'] = [];
            // EIP1559
            $transactionData['maxFeePerGas'] = '0x' . $gasPrice->getHex();
            $gasPriceTip = \BitWasp\Buffertools\Buffer::int(intval($gasPriceTip));
            $transactionData['maxPriorityFeePerGas'] = '0x' . $gasPriceTip->getHex();
        }

        return self::_make_tx($transactionData);
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
     * _make_tx
     *
     * @param  array $data
     * @return Tx
     */
    private static function _make_tx(array $data): Tx
    {
        return new class($data) implements Tx
        {
            /**
             * tx_array
             *
             * @var array
             */
            private $tx_array;

            /**
             * __construct
             *
             * @param  array $address
             * @return void
             */
            function __construct(array $tx_array)
            {
                $this->tx_array = $tx_array;
            }

            /**
             * get_tx_array
             *
             * @return array
             */
            function get_tx_array(): array
            {
                return $this->tx_array;
            }
            /**
             * get_tx_cost_estimate
             *
             * @return string
             */
            function get_tx_cost_estimate(): string
            {
                $gas = new \phpseclib3\Math\BigInteger($this->tx_array['gas'], 16);
                $price = new \phpseclib3\Math\BigInteger(0);
                if (isset($this->tx_array['gasPrice'])) {
                    $price = new \phpseclib3\Math\BigInteger($this->tx_array['gasPrice'], 16);
                } else if (isset($this->tx_array['maxFeePerGas'])) {
                    $price = new \phpseclib3\Math\BigInteger($this->tx_array['maxFeePerGas'], 16);
                }
                $cost = $gas->multiply($price);
                return $cost->toString();
            }
        };
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
