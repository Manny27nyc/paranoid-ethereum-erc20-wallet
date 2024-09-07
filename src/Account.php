<?php

namespace Paranoid;

use Paranoid\Address;
use Paranoid\Blockchain;
use Paranoid\Tx;
use Paranoid\Wei;
use Paranoid\NativeWei;
use Paranoid\ERC20;

final class Account
{
    /**
     * private_key
     *
     * @var string
     */
    private $private_key;
    /**
     * address
     *
     * @var Address
     */
    private $address;

    /**
     * __construct
     *
     * @param  string $private_key
     * @return void
     * @throws \InvalidArgumentException
     */
    function __construct($private_key)
    {
        if (empty($private_key)) {
            throw new \InvalidArgumentException('Empty private key specified');
        }
        $this->private_key = $private_key;
        $this->address = $this->_get_address();
    }

    /**
     * generate_new
     *
     * @return Account
     */
    static function generate_new(): Account
    {
        $random = new \BitWasp\Bitcoin\Crypto\Random\Random();
        $privateKeyBuffer = $random->bytes(32);
        $privateKeyHex = $privateKeyBuffer->getHex();
        return new Account($privateKeyHex);
    }

    /**
     * get_private_key
     *
     * @return string
     */
    function get_private_key(): string
    {
        return $this->private_key;
    }

    /**
     * get_address
     *
     * @return Address
     */
    function get_address(): Address
    {
        return $this->address;
    }

    /**
     * get_nonce
     *
     * @param  Blockchain $blockchain
     * @return int
     */
    function get_nonce(Blockchain $blockchain): int
    {
        return $blockchain->get_account_nonce($this);
    }

    /**
     * get_token_balance
     *
     * @param  ERC20 $token
     * @return Wei
     */
    function get_token_balance(ERC20 $token): Wei
    {
        return $token->get_account_balance($this);
    }

    /**
     * get_balance
     *
     * @param  ERC20 $token
     * @return NativeWei
     */
    function get_balance(NativeCoin $coin): NativeWei
    {
        return $coin->get_account_balance($this);
    }

    /**
     * sign_tx
     *
     * @param  Tx $tx
     * @return TxSigned
     */
    function sign_tx(Tx $tx): TxSigned
    {
        $transaction = new \Web3p\EthereumTx\Transaction($tx->get_tx_array());
        $signedTransaction = "0x" . $transaction->sign($this->private_key);
        return self::_makeTxSigned($signedTransaction);
    }

    /**
     * _makeTxSigned
     *
     * @param  string $data
     * @return TxSigned
     */
    private static function _makeTxSigned(string $data): TxSigned
    {
        return new class($data) implements TxSigned
        {
            /**
             * tx_array
             *
             * @var string
             */
            private $tx_signed;

            /**
             * __construct
             *
             * @param  string $tx_signed
             * @return void
             */
            function __construct(string $tx_signed)
            {
                $this->tx_signed = $tx_signed;
            }

            /**
             * get_tx_signed_raw
             *
             * @return string
             */
            function get_tx_signed_raw(): string
            {
                return $this->tx_signed;
            }
        };
    }

    /**
     * _get_address
     *
     * @return Address
     */
    private function _get_address(): Address
    {
        $privateKeyFactory = new \BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory();
        $privateKey = $privateKeyFactory->fromHexUncompressed($this->private_key);

        $pubKeyHex = $privateKey->getPublicKey()->getHex();
        $hash = \kornrunner\Keccak::hash(substr(hex2bin($pubKeyHex), 1), 256);
        $address = '0x' . substr($hash, 24);
        return new Address($address);
    }
}
