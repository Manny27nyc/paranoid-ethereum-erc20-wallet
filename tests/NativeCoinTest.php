<?php

declare(strict_types=1);

use Paranoid\Address;
use Paranoid\Account;
use Paranoid\NativeCoin;

final class NativeCoinTest extends \Tests\TestCaseBase
{
    public function testConstruct(): void
    {
        $coin = new NativeCoin($this->blockchain);
        $this->assertEquals(18, $coin->get_decimals());
    }

    public function testGetAddressBalance(): void
    {
        $to = new Address('0x15d34AAf54267DB7D7c367839AAf71A00a2C6A65');
        $coin = new NativeCoin($this->blockchain);
        $balance = $coin->get_address_balance($to);
        $this->assertEquals('10000000000000000000000', $balance->get_wei_str());
    }

    public function testMakeAmountToSend(): void
    {
        $pk = 'de9be858da4a475276426320d5e9262ecfc3ba460bfac56360bfa6c4c28b4ee0';
        $account = new Account($pk);
        $amount = 0.01;
        $coin = new NativeCoin($this->blockchain);
        $amount_wei = $coin->make_amount_to_send($account, $amount);

        $this->assertEquals('10000000000000000', $amount_wei->get_wei_str());
    }

    public function testMakeTransaction(): void
    {
        $private_key = '47e179ec197488593b187f80a00eb0da91f1b9d0b13f8733639f19c30a34926a';
        $to = new Address('0xa0Ee7A142d267C1f36714E4a8F75612F20a79720');
        $amount = 0.01;

        $coin = new NativeCoin($this->blockchain);

        $user_account = new Account($private_key);

        $amount_wei = $coin->make_amount_to_send($user_account, $amount);

        $tx = $coin->make_transaction($user_account, $to, $amount_wei);
        $cost_estimate = $tx->get_tx_cost_estimate();
        $this->assertGreaterThan(0, $cost_estimate);

        $tx_signed = $user_account->sign_tx($tx);
        $this->assertGreaterThan(2, strlen($tx_signed->get_tx_signed_raw()));
    }
}
