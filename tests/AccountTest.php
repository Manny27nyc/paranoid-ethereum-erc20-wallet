<?php

declare(strict_types=1);

use Paranoid\Account;
use Paranoid\Address;
use Paranoid\NativeCoin;
use Paranoid\ERC20;

final class AccountTest extends \Tests\TestCaseBase
{
    public function testConstructEmptyKey(): void
    {
        // $this->assertTrue(false);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Empty private key specified');
        new Account('');
    }

    public function testConstruct(): void
    {
        $pk = 'df57089febbacf7ba0bc227dafbffa9fc08a93fdc68e1e42411a14efcf23656e';
        $account = new Account($pk);
        $this->assertEquals('0x8626f6940E2eb28930eFb4CeF49B2d1F2C9C1199', $account->get_address()->get_address());
        $this->assertEquals($pk, $account->get_private_key());
    }

    public function testGenerateNew(): void
    {
        $account = Account::generate_new();
        $this->assertNotEmpty($account->get_address()->get_address());
        $this->assertNotEmpty($account->get_private_key());
    }

    public function testNonce(): void
    {
        $pk = 'de9be858da4a475276426320d5e9262ecfc3ba460bfac56360bfa6c4c28b4ee0';
        $account = new Account($pk);
        $this->assertEquals(0, $account->get_nonce($this->blockchain));

        $pk = 'ac0974bec39a17e36ba4a6b4d238ff944bacb478cbed5efcae784d7bf4f2ff80';
        $account = new Account($pk);
        $this->assertGreaterThan(0, $account->get_nonce($this->blockchain));
    }

    public function testGetTokenBalance(): void
    {
        $pk = self::ERC20_TOKEN_OWNER_PK;
        $account = new Account($pk);
        $coin = new ERC20(new Address(self::ERC20_TOKEN_ADDRESS), $this->blockchain);
        $balance = $account->get_token_balance($coin);
        $this->assertEquals('1000000000000000000000000', $balance->get_wei_str());
    }

    public function testNativeBalance(): void
    {
        $pk = '2a871d0798f97d79848a013d4936a73bf4cc922c825d33c1cf7073dff6d409c6';
        $account = new Account($pk);
        $coin = new NativeCoin($this->blockchain);
        $native_wei = $account->get_balance($coin);
        $this->assertEquals('10000000000000000000000', $native_wei->get_wei_str());
    }
}
