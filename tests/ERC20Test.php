<?php

declare(strict_types=1);

use Paranoid\Address;
use Paranoid\Account;
use Paranoid\NativeCoin;
use Paranoid\ERC20;

final class ERC20Test extends \Tests\TestCaseBase
{
    public function testConstructNotAContractAddress(): void
    {
        $contract_address = new Address(self::ERC20_TOKEN_OWNER_ADDRESS);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Token address is not a contract: ' . $contract_address->get_address());

        new ERC20($contract_address, $this->blockchain);
    }

    public function testConstruct(): void
    {
        $coin = new ERC20(new Address(self::ERC20_TOKEN_ADDRESS), $this->blockchain);
        $this->assertEquals(self::ERC20_TOKEN_ADDRESS, strtolower($coin->get_contract_address()->get_address()));
        $this->assertEquals(ERC20::ERC20_ABI, $coin->get_abi());
        $this->assertEquals(18, $coin->get_decimals());
    }

    public function testGetAddressBalance(): void
    {
        $to = new Address(self::ERC20_TOKEN_OWNER_ADDRESS);
        $coin = new ERC20(new Address(self::ERC20_TOKEN_ADDRESS), $this->blockchain);
        $balance = $coin->get_address_balance($to);
        $this->assertEquals('1000000000000000000000000', $balance->get_wei_str());
    }

    public function testGetAccountBalance(): void
    {
        $pk = self::ERC20_TOKEN_OWNER_PK;
        $account = new Account($pk);
        $coin = new ERC20(new Address(self::ERC20_TOKEN_ADDRESS), $this->blockchain);
        $balance = $coin->get_account_balance($account);
        $this->assertEquals('1000000000000000000000000', $balance->get_wei_str());
    }

    public function testMakeAmountToSend(): void
    {
        $pk = self::ERC20_TOKEN_OWNER_PK;
        $account = new Account($pk);
        $amount = 10.0;
        $coin = new ERC20(new Address(self::ERC20_TOKEN_ADDRESS), $this->blockchain);
        $amount_wei = $coin->make_amount_to_send($account, $amount);

        $this->assertEquals('10000000000000000000', $amount_wei->get_wei_str());
    }

    public function testMakeAmountToSendInvalidData(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $pk = '47c99abed3324a2707c28affff1267e45918ec8c3f20b8aa892e8b065d2942dd';
        $account = new Account($pk);
        $amount = 10.0;
        $coin = new ERC20(new Address(self::ERC20_TOKEN_ADDRESS), $this->blockchain);
        // throw
        $coin->make_amount_to_send($account, $amount);
    }

    public function testMakeTransferData(): void
    {
        $private_key = self::ERC20_TOKEN_OWNER_PK;
        $to = new Address('0xa0Ee7A142d267C1f36714E4a8F75612F20a79720');
        $amount = 0.01;

        $coin = new ERC20(new Address(self::ERC20_TOKEN_ADDRESS), $this->blockchain);

        $user_account = new Account($private_key);

        $amount_wei = $coin->make_amount_to_send($user_account, $amount);
        $tx_data = $coin->make_transfer_data($to, $amount_wei);

        $this->assertEquals('a9059cbb000000000000000000000000a0ee7a142d267c1f36714e4a8f75612f20a79720000000000000000000000000000000000000000000000000002386f26fc10000', $tx_data->get_data());
    }

    public function testMakeTransferDataInvalidData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Wei tokens_amount from another token provided');

        $private_key = self::ERC20_TOKEN_OWNER_PK;
        $to = new Address('0xa0Ee7A142d267C1f36714E4a8F75612F20a79720');
        $amount = 0.01;

        $coin = new ERC20(new Address(self::ERC20_TOKEN_ADDRESS), $this->blockchain);
        $coin2 = new ERC20(new Address(self::ERC20_TOKEN_ADDRESS2), $this->blockchain);

        $user_account = new Account($private_key);

        $amount_wei = $coin2->make_amount_to_send($user_account, $amount);
        $coin->make_transfer_data($to, $amount_wei);
    }

    public function testMakeTransaction(): void
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
    }
}
