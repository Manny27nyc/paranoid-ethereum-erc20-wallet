<?php

namespace Paranoid;

use Paranoid\Contract;
use Paranoid\Address;
use Paranoid\TxSigned;
use Paranoid\NativeWei;

final class Blockchain
{
    /**
     * http_provider
     *
     * @var \Web3\Providers\HttpProvider
     */
    private $http_provider;
    /**
     * web3
     *
     * @var \Web3
     */
    private $web3;
    /**
     * network_id
     *
     * @var int
     */
    private $network_id;
    /**
     * _is_eip1559
     *
     * @var bool
     */
    private $_is_eip1559;
    /**
     * options
     *
     * @var array
     */
    private $options;

    private $_address_to_code_cache = [];

    /**
     * __construct
     *
     * @param  string $provider_url
     * @param  array $options 
     *   [
     *       'gas_limit' => 200000,
     *       'max_gas_price' => 21,
     *       'network_timeout' => 10,
     *   ]
     * @return void
     */
    function __construct(string $provider_url, array $options = [])
    {
        if (empty($provider_url)) {
            throw new \InvalidArgumentException('Empty provider_url value provided');
        }
        $this->options = array_merge([
            'gas_limit' => 200000,
            'max_gas_price' => 21,
            'network_timeout' => 10,
        ], $options);
        $this->http_provider = $this->_get_provider($provider_url, $this->options['network_timeout']);
        $this->web3 = new \Web3\Web3($this->http_provider);
    }

    /**
     * get_provider_url
     *
     * @return string
     */
    function get_provider_url(): string
    {
        return $this->http_provider->getRequestManager()->getHost();
    }

    /**
     * get_option
     *
     * @param  string $option_name
     * @return int
     */
    function get_option($option_name): int
    {
        if (!isset($this->options[$option_name])) {
            throw new \InvalidArgumentException('Unknown option name requested: ' . $option_name);
        }
        return $this->options[$option_name];
    }

    /**
     * get_network_id
     *
     * @return int
     */
    function get_network_id(): int
    {
        if (is_null($this->network_id)) {
            $this->network_id = $this->_get_network_id();
        }
        return $this->network_id;
    }

    /**
     * get_native_coin_decimals
     *
     * @return int
     */
    function get_native_coin_decimals(): int
    {
        return 18;
    }

    /**
     * call_contract_method
     *
     * @param  Contract $c
     * @param  string $method
     * @param  array $method_args
     * @param  callable $callback
     * @return void
     */
    function call_contract_method(Contract $c, string $method, array $method_args, callable $callback): void
    {
        $contract = new \Web3\Contract($this->http_provider, $c->get_abi());
        $contract->at($c->get_contract_address()->get_address())->call(...array_merge([$method], $method_args, [$callback]));
    }

    /**
     * get_contract_method_data
     *
     * @param  Congtract $c
     * @param  string $method
     * @param  array $method_args
     * @return string
     */
    function get_contract_method_data(Contract $c, string $method, array $method_args): string
    {
        $contract = new \Web3\Contract($this->http_provider, $c->get_abi());
        /**
         * @var string
         */
        $data = $contract->at($c->get_contract_address()->get_address())->getData(...array_merge([$method], $method_args));
        return $data;
    }

    /**
     * get_contract_code
     *
     * @param  Contract $contract
     * @return string
     * @throws \Exception
     */
    function get_contract_code(Contract $contract): string
    {
        return $this->_get_contract_code($contract->get_contract_address());
    }

    /**
     * _get_contract_code
     *
     * @param  Address $contract_address
     * @return string
     * @throws \Exception
     */
    private function _get_contract_code(Address $contract_address): string
    {
        if (!isset($this->_address_to_code_cache[$contract_address->get_address()])) {
            $ret = null;
            $err = null;

            $this->web3->eth->getCode($contract_address->get_address(), function ($error, $code) use (&$ret, &$err) {
                if ($error !== null) {
                    $err = $error;
                    return;
                }
                $ret = $code;
            });

            if (!is_null($err)) {
                throw new \Exception('Failed to get contract code: ' . $err);
            }

            if (is_null($ret)) {
                throw new \Exception('Failed to get contract code');
            }
            $this->_address_to_code_cache[$contract_address->get_address()] = $ret;
        }

        return $this->_address_to_code_cache[$contract_address->get_address()];
    }

    /**
     * get_account_nonce
     *
     * @param  Account $account
     * @return int
     * @throws \Exception
     */
    function get_account_nonce(Account $account): int
    {
        $ret = 0;
        $err = null;
        $this->web3->eth->getTransactionCount($account->get_address()->get_address(), function ($error, $transactionCount) use (&$ret, &$err) {
            if ($error !== null) {
                $err = $error;
                return;
            }
            $ret = intval($transactionCount->toString());
        });
        if (!is_null($err)) {
            throw new \Exception('Failed to get account nonce: ' . $err);
        }

        if (is_null($ret)) {
            throw new \Exception('Failed to get account nonce');
        }
        return $ret;
    }

    /**
     * send_transaction
     *
     * @param  TxSigned $tx_signed
     * @return string
     * @throws \Exception
     */
    function send_transaction(TxSigned $tx_signed): string
    {
        $ret = null;
        $err = null;

        $this->web3->eth->sendRawTransaction($tx_signed->get_tx_signed_raw(), function ($error, $transaction) use (&$ret, &$err) {
            if ($err !== null) {
                $err = $error;
                return;
            }
            $ret = $transaction;
        });

        if (!is_null($err)) {
            throw new \Exception('Failed to send transaction: ' . $err);
        }

        if (is_null($ret)) {
            throw new \Exception('Failed to send transaction');
        }

        return $ret;
    }

    /**
     * get_address_balance
     *
     * @param  Address $address
     * @return string
     * @throws \Exception
     */
    function get_address_balance(Address $address): string
    {
        $ret = null;
        $err = null;

        $this->web3->eth->getBalance($address->get_address(), function ($error, $balance) use (&$ret, &$err) {
            if ($error !== null) {
                $err = $error;
                return;
            }
            $ret = $balance;
        });

        if (!is_null($err)) {
            throw new \Exception('Failed to get account balance: ' . $err);
        }

        if (is_null($ret)) {
            throw new \Exception('Failed to get account balance');
        }

        return $ret;
    }

    /**
     * make_native_wei
     *
     * @param  string $wei
     * @return NativeWei
     */
    function make_native_wei(string $wei): NativeWei
    {
        return new class($wei, $this) implements NativeWei
        {
            /**
             * wei
             *
             * @var string
             */
            private $wei;
            /**
             * blockchain
             *
             * @var Blockchain
             */
            private $blockchain;

            /**
             * __construct
             *
             * @param  string $wei
             * @return void
             */
            function __construct(string $wei, Blockchain $blockchain)
            {
                $this->wei = $wei;
                $this->blockchain = $blockchain;
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
                return $this->blockchain->get_native_coin_decimals();
            }
        };
    }

    /**
     * is_eip1559
     *
     * @return bool
     */
    function is_eip1559(): bool
    {
        if (is_null($this->_is_eip1559)) {
            $this->_is_eip1559 = $this->_get_is_eip1559();
        }
        return $this->_is_eip1559;
    }

    /**
     * get_latest_block
     *
     * @return object The latest block object in the blockchain configured
     * @throws \Exception
     */
    function get_latest_block(): object
    {
        $ret = null;
        $err = null;
        $this->web3->eth->getBlockByNumber('latest', false, function ($error, $block) use (&$ret, &$err) {
            if ($error !== null) {
                $err = $error;
                return;
            }
            $ret = $block;
        });

        if (!is_null($err)) {
            throw new \Exception('Failed to get latest block: ' . $err);
        }

        if (is_null($ret)) {
            throw new \Exception('Failed to get latest block');
        }

        return $ret;
    }

    /**
     * make_transaction
     *
     * @param  Account $from
     * @param  Address $to
     * @param  TxData $tx_data
     * @param  NativeWei $tx_value
     * @return Tx
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    function make_transaction(Account $from, Address $to, TxData $tx_data, NativeWei $tx_value): Tx
    {
        $nonce = $this->get_account_nonce($from);
        $nonceb = \BitWasp\Buffertools\Buffer::int(intval($nonce));
        $gas_limit = $this->_get_gas_limit($to);
        $gasLimit = \BitWasp\Buffertools\Buffer::int($gas_limit);

        $data = $tx_data->get_data();
        $value = $tx_value->get_wei_str();
        $value_bn = new \phpseclib3\Math\BigInteger($value);
        $value_hex = $value_bn->toHex();
        $transactionData = [
            'from' => $from->get_address()->get_address(),
            'nonce' => '0x' . $nonceb->getHex(),
            'to' => strtolower($to->get_address()),
            'gas' => '0x' . ltrim($gasLimit->getHex(), '0'),
            'value' => '0x' . (empty($value_hex) ? '0' : $value_hex),
            'chainId' => $this->get_network_id(),
            'data' => !empty($data) ? '0x' . $data : null,
        ];

        if (21000 < $gas_limit) {
            $gasEstimate = $this->_get_gas_estimate($transactionData);
            if ($gasLimit->getHex() === $gasEstimate->toHex()) {
                throw new \Exception("Too low gas_limit option specified: " . $gasLimit->getHex());
            }
            $transactionData['gas'] = '0x' . $gasEstimate->toHex();
        }
        unset($transactionData['from']);

        $transactionData = array_merge(
            $transactionData,
            $this->_get_fee_fields()
        );

        return self::_make_tx($transactionData);
    }

    private function _get_gas_limit(Address $to): int
    {
        if ('0x' === $this->_get_contract_code($to)) {
            return 21000;
        }
        return intval($this->get_option('gas_limit'));
    }

    /**
     * _get_gas_price_wei
     *
     * @return int
     */
    private function _get_gas_price_wei(): int
    {
        $cache_gas_price_wei = $this->_query_gas_price_wei();

        $gasPriceWei = doubleval($cache_gas_price_wei['gas_price']);
        if (is_null($cache_gas_price_wei['gas_price_tip'])) {
            // only if pre-EIP1559
            $gasPriceMaxWei = doubleval($this->_get_default_gas_price_wei()['gas_price']);

            if ($gasPriceMaxWei < $gasPriceWei) {
                $gasPriceWei = $gasPriceMaxWei;
            }
        }
        return intval($gasPriceWei);
    }

    /**
     * _get_gas_price_tip_wei
     *
     * @return int
     */
    private function _get_gas_price_tip_wei(): int
    {
        $cache_gas_price_wei = $this->_query_gas_price_wei();

        if (is_null($cache_gas_price_wei['gas_price_tip'])) {
            return null;
        }

        $gasPriceTipWei = doubleval($cache_gas_price_wei['gas_price_tip']);
        $gasPriceTipMaxWei = doubleval($this->_get_default_gas_price_wei()['gas_price']);

        if ($gasPriceTipMaxWei < $gasPriceTipWei) {
            $gasPriceTipWei = $gasPriceTipMaxWei;
        }
        return intval($gasPriceTipWei);
    }

    /**
     * _get_fee_fields
     *
     * @return array
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    private function _get_fee_fields(): array
    {
        $transactionData = [
            'chainId' => $this->get_network_id(),
        ];

        $gasPrice = $this->_get_gas_price_wei();
        $gasPrice = \BitWasp\Buffertools\Buffer::int(intval($gasPrice));

        if ($this->is_eip1559()) {
            $transactionData['accessList'] = [];
            // EIP1559
            $transactionData['maxFeePerGas'] = '0x' . $gasPrice->getHex();

            $gasPriceTip = $this->_get_gas_price_tip_wei();
            $gasPriceTip = \BitWasp\Buffertools\Buffer::int(intval($gasPriceTip));
            $transactionData['maxPriorityFeePerGas'] = '0x' . $gasPriceTip->getHex();
        } else {
            // pre-EIP1559
            $transactionData['gasPrice'] = '0x' . $gasPrice->getHex();
        }

        return $transactionData;
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
     * Get the HttpProvider connection object
     *
     * @param  string $provider_url
     * @param  int $network_timeout
     * @return \Web3\Providers\HttpProvider
     */
    private function _get_provider(string $provider_url, int $network_timeout): \Web3\Providers\HttpProvider
    {
        $requestManager = new \Web3\RequestManagers\HttpRequestManager($provider_url, $network_timeout);
        $_httpProvider = new \Web3\Providers\HttpProvider($requestManager);
        return $_httpProvider;
    }

    /**
     * _get_network_id
     *
     * @return int
     * @throws \Exception
     */
    private function _get_network_id(): int
    {
        $ret = null;
        $err = null;

        $web3 = new \Web3\Web3($this->http_provider);
        $net = $web3->net;
        $net->version(function ($error, $version) use (&$ret, &$err) {
            if ($error !== null) {
                $err = $error;
                return;
            }
            $ret = $version;
        });

        if (!is_null($err)) {
            throw new \Exception('Failed to get network id: ' . $err);
        }

        if (is_null($ret)) {
            throw new \Exception('Failed to get network id');
        }

        $ret = intval($ret);
        return $ret;
    }

    /**
     * _query_gas_price_wei
     *
     * @return array
     */
    private function _query_gas_price_wei(): array
    {
        $gasPriceWei = null;
        $gasPriceTipWei = null;
        $default_gas_price_wei = $this->_get_default_gas_price_wei();

        $isEIP1559 = $this->is_eip1559();
        if (!$isEIP1559) {
            $gasPriceWei = $this->_query_web3_gas_price_wei();
        } else {
            $block = $this->get_latest_block();
            $gasPriceAndTipWei = $this->_query_web3_gas_price_wei();
            $gasPriceTipWeiBI = (new \phpseclib3\Math\BigInteger($gasPriceAndTipWei))->subtract(new \phpseclib3\Math\BigInteger($block->baseFeePerGas, 16));
            if ($gasPriceTipWeiBI->compare(new \phpseclib3\Math\BigInteger(0)) < 0) {
                $gasPriceTipWeiBI = new \phpseclib3\Math\BigInteger(1000000000); // 1 Gwei
            }
            $default_gas_price_wei_BI = new \phpseclib3\Math\BigInteger($default_gas_price_wei['gas_price']);
            if ($default_gas_price_wei_BI->compare($gasPriceTipWeiBI) < 0) {
                $gasPriceTipWeiBI = $default_gas_price_wei_BI;
            }
            $gasPriceTipWei = $gasPriceTipWeiBI->toString();
            $gasPriceWei = (new \phpseclib3\Math\BigInteger($block->baseFeePerGas, 16))
                ->multiply(new \phpseclib3\Math\BigInteger(2)) // twice the last value to ensure it will fit
                ->add($gasPriceTipWeiBI);
            $gasPriceWei = $gasPriceWei->toString();
            if ('0' === $gasPriceWei) {
                return $default_gas_price_wei;
            }
        }

        if (is_null($gasPriceWei)) {
            return $default_gas_price_wei;
        }

        $cache_gas_price = array('gas_price' => $gasPriceWei, 'gas_price_tip' => $gasPriceTipWei);

        return $cache_gas_price;
    }

    /**
     * _get_default_gas_price_wei
     *
     * @return array
     */
    private function _get_default_gas_price_wei(): array
    {
        $gasPriceMaxGwei = doubleval($this->options['max_gas_price']);
        return array('gas_price' => intval(ceil(floatval($gasPriceMaxGwei) * 1000000000)), 'gas_price_tip' => null);
    }

    /**
     * _is_eip1559
     *
     * @return bool
     */
    private function _get_is_eip1559(): bool
    {
        $block = $this->get_latest_block();
        $isEIP1559 = property_exists($block, 'baseFeePerGas');
        return $isEIP1559;
    }

    /**
     * _get_gas_estimate
     *
     * @param  mixed $transactionParamsArray
     * @return \phpseclib3\Math\BigInteger
     * @throws \Exception
     */
    private function _get_gas_estimate(array $transactionParamsArray): \phpseclib3\Math\BigInteger
    {
        $ret = null;
        $err = null;
        $transactionParamsArrayCopy = $transactionParamsArray;
        unset($transactionParamsArrayCopy['nonce']);
        unset($transactionParamsArrayCopy['chainId']);
        $this->web3->eth->estimateGas($transactionParamsArrayCopy, function ($error, $gas) use (&$ret, &$err) {
            if ($error !== null) {
                $err = $error;
                return;
            }
            $ret = $gas;
        });

        if (!is_null($err)) {
            throw new \Exception('Failed to estimate gas: ' . $err);
        }

        if (is_null($ret)) {
            throw new \Exception('Failed to estimate gas');
        }

        return $ret;
    }

    /**
     * _query_web3_gas_price_wei
     *
     * @return string
     * @throws \Exception
     */
    private function _query_web3_gas_price_wei(): string
    {
        $ret = null;
        $err = null;
        $this->web3->eth->gasPrice(function ($error, $gasPrice) use (&$ret, &$err) {
            if ($error !== null) {
                $err = $error;
                return;
            }
            $ret = $gasPrice;
        });

        if (!is_null($err)) {
            throw new \Exception('Failed to get gas price: ' . $err);
        }

        if (is_null($ret)) {
            throw new \Exception('Failed to get gas price');
        }

        return $ret->toString();
    }
}
