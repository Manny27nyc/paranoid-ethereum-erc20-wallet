<?php

declare(strict_types=1);

use Paranoid\Blockchain;
use Paranoid\NativeCoin;
use Paranoid\Account;
use Paranoid\Address;
use Paranoid\ERC20;

final class BlockchainTest extends \Tests\TestCaseBase
{
    public function testConstructEmptyKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Empty provider_url value provided');
        new Blockchain('');
    }

    public function testConstructWrongProviderURL(): void
    {
        $this->expectException(\Exception::class);
        $b = new Blockchain(self::BLOCKCHAIN_WRONG_PROVIDER_URL);
        $b->get_network_id();
    }

    public function testConstruct(): void
    {
        $b = new Blockchain(self::BLOCKCHAIN_PROVIDER_URL, [
            'gas_limit' => 200001,
            'max_gas_price' => 22,
            'network_timeout' => 11,
        ]);
        $this->assertEquals(200001, $b->get_option('gas_limit'));
        $this->assertEquals(22, $b->get_option('max_gas_price'));
        $this->assertEquals(11, $b->get_option('network_timeout'));
        $this->assertEquals(self::BLOCKCHAIN_PROVIDER_URL, $b->get_provider_url());
    }

    public function testOptions(): void
    {
        $this->assertEquals(200000, $this->blockchain->get_option('gas_limit'));
    }

    public function testOptionsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $option_name = 'non-existing-option';
        $this->expectExceptionMessage('Unknown option name requested: ' . $option_name);

        $this->blockchain->get_option($option_name);
    }

    public function testProviderURL(): void
    {
        $this->assertEquals(self::BLOCKCHAIN_PROVIDER_URL, $this->blockchain->get_provider_url());
    }

    public function testNetworkId(): void
    {
        $this->assertEquals(31337, $this->blockchain->get_network_id());
    }

    public function testNativeCoinDecimals(): void
    {
        $this->assertEquals(18, $this->blockchain->get_native_coin_decimals());
    }

    public function testSendTxNoData(): void
    {
        $private_key = 'ea6c44ac03bff858b476bba40716402b03e41b8e97e276d1baec7c37d42484a0';
        $to = new Address('0xa0Ee7A142d267C1f36714E4a8F75612F20a79720');
        $amount = 0.01;

        $coin = new NativeCoin($this->blockchain);

        $user_account = new Account($private_key);

        $balance_before = $coin->get_address_balance($to);
        $this->assertNotEquals('0', $balance_before->get_wei_str());

        $amount_wei = $coin->make_amount_to_send($user_account, $amount);

        $tx = $coin->make_transaction($user_account, $to, $amount_wei);
        $cost_estimate = $tx->get_tx_cost_estimate();
        $this->assertGreaterThan(0, $cost_estimate);

        $tx_signed = $user_account->sign_tx($tx);
        $this->assertGreaterThan(2, strlen($tx_signed->get_tx_signed_raw()));

        $tx_hash = $this->blockchain->send_transaction($tx_signed);
        $this->assertNotEmpty($tx_hash);

        $balance_after = $coin->get_address_balance($to);

        $balance_before_bn = new \phpseclib3\Math\BigInteger($balance_before->get_wei_str());
        $balance_after_bn = new \phpseclib3\Math\BigInteger($balance_after->get_wei_str());
        $amount_bn = new \phpseclib3\Math\BigInteger('10000000000000000');
        $ret = $balance_before_bn->add($amount_bn);
        $this->assertEquals(0, $balance_after_bn->compare($ret));
    }

    public function testSendTxWithData(): void
    {
        $private_key = self::ERC20_TOKEN_OWNER_PK;
        $to = new Address('0xa0Ee7A142d267C1f36714E4a8F75612F20a79720');
        $amount = 0.01;

        $coin = new ERC20(new Address(self::ERC20_TOKEN_ADDRESS), $this->blockchain);

        $user_account = new Account($private_key);

        $amount_wei = $coin->make_amount_to_send($user_account, $amount);
        $tx_data = $coin->make_transfer_data($to, $amount_wei);

        $tx = $coin->make_transaction($user_account, $tx_data, $this->blockchain->make_native_wei('0'));
        $cost_estimate = $tx->get_tx_cost_estimate();
        $this->assertGreaterThan(0, $cost_estimate);

        $tx_signed = $user_account->sign_tx($tx);
        $this->assertGreaterThan(2, strlen($tx_signed->get_tx_signed_raw()));

        $balance_before = $coin->get_address_balance($to);

        $tx_hash = $this->blockchain->send_transaction($tx_signed);
        $this->assertNotEmpty($tx_hash);

        $balance_after = $coin->get_address_balance($to);

        $balance_before_bn = new \phpseclib3\Math\BigInteger($balance_before->get_wei_str());
        $balance_after_bn = new \phpseclib3\Math\BigInteger($balance_after->get_wei_str());
        $amount_bn = new \phpseclib3\Math\BigInteger('10000000000000000');
        $ret = $balance_before_bn->add($amount_bn);
        $this->assertEquals(0, $balance_after_bn->compare($ret));

        // send tokens back
        {
            $private_key = '2a871d0798f97d79848a013d4936a73bf4cc922c825d33c1cf7073dff6d409c6';
            $to = new Address(self::ERC20_TOKEN_OWNER_ADDRESS);
            $amount = 0.01;

            $coin = new ERC20(new Address(self::ERC20_TOKEN_ADDRESS), $this->blockchain);
            $user_account = new Account($private_key);

            $amount_wei = $coin->make_amount_to_send($user_account, $amount);
            $tx_data = $coin->make_transfer_data($to, $amount_wei);

            $tx = $coin->make_transaction($user_account, $tx_data, $this->blockchain->make_native_wei('0'));
            $tx_signed = $user_account->sign_tx($tx);
            $tx_hash = $this->blockchain->send_transaction($tx_signed);
        }
    }

    public function testGetAddressBalance(): void
    {
        $to = new Address('0x23618e81E3f5cdF7f54C3d65f7FBc0aBf5B21E8f');
        $balance = $this->blockchain->get_address_balance($to);
        $this->assertEquals('10000000000000000000000', $balance);
    }

    public function testMakeNativeWei(): void
    {
        $balance = $this->blockchain->make_native_wei('1000');
        $this->assertEquals('1000', $balance->get_wei_str());
        $this->assertEquals(18, $balance->get_decimals());
    }

    public function testIsEIP1559(): void
    {
        $this->blockchain->is_eip1559();
        $this->expectNotToPerformAssertions();
    }

    public function testGetLatestBlock(): void
    {
        $block = $this->blockchain->get_latest_block();
        $this->expectNotToPerformAssertions();
    }

    public function testGetContractCodeWrongProviderURL(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Could not resolve host');
        $b = new Blockchain(self::BLOCKCHAIN_WRONG_PROVIDER_URL);
        // throw
        new ERC20(new Address(self::ERC20_TOKEN_ADDRESS), $b);
    }

    public function testGetAccountNonceWrongProviderURL(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Could not resolve host');
        $b = new Blockchain(self::BLOCKCHAIN_WRONG_PROVIDER_URL);
        $pk = self::ERC20_TOKEN_OWNER_PK;
        $account = new Account($pk);
        // throw
        $account->get_nonce($b);
    }

    public function testSendTransactionWrongProviderURL(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Could not resolve host');
        $b = new Blockchain(self::BLOCKCHAIN_WRONG_PROVIDER_URL);

        $pk = self::ERC20_TOKEN_OWNER_PK;
        $to = new Address('0xa0Ee7A142d267C1f36714E4a8F75612F20a79720');
        $amount = 0.01;

        $coin = new NativeCoin($b);
        $user_account = new Account($pk);
        $amount_wei = $coin->make_amount_to_send($user_account, $amount);
        $tx = $coin->make_transaction($user_account, $to, $amount_wei);
        $tx_signed = $user_account->sign_tx($tx);
        // throw
        $b->send_transaction($tx_signed);
    }

    public function testGetAddressBalanceWrongProviderURL(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Could not resolve host');
        $b = new Blockchain(self::BLOCKCHAIN_WRONG_PROVIDER_URL);

        $to = new Address('0xa0Ee7A142d267C1f36714E4a8F75612F20a79720');
        $coin = new NativeCoin($b);

        // throw
        $coin->get_address_balance($to);
    }

    public function testGetLatestBlockWrongProviderURL(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Could not resolve host');
        $b = new Blockchain(self::BLOCKCHAIN_WRONG_PROVIDER_URL);

        // throw
        $b->get_latest_block();
    }
}
