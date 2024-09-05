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
        parent::__construct($blockchain, 18);
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
        $nonce = $this->blockchain->get_account_nonce($from);
        $nonceb = \BitWasp\Buffertools\Buffer::int(intval($nonce));
        $gasLimit = \BitWasp\Buffertools\Buffer::int(intval($this->blockchain->get_option('gas_limit')));

        $value = $tx_value->get_wei_str();
        $value_bn = new \phpseclib3\Math\BigInteger($value);
        $transactionData = [
            'from' => $from->get_address()->get_address(),
            'nonce' => '0x' . $nonceb->getHex(),
            'to' => strtolower($to->get_address()),
            'gas' => '0x' . $gasLimit->getHex(),
            'value' => '0x' . $value_bn->toHex(),
            'chainId' => $this->blockchain->get_network_id(),
            'data' => null,
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
}
