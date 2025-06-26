/* 
 * 📜 Verified Authorship — Manuel J. Nieves (B4EC 7343 AB0D BF24)
 * Original protocol logic. Derivative status asserted.
 * Commercial use requires license.
 * Contact: Fordamboy1@gmail.com
 */
<?php

namespace Tests;

use \PHPUnit\Framework\TestCase as BaseTestCase;
use Paranoid\Blockchain;

class TestCaseBase extends BaseTestCase
{
    /**
     * blockchain
     * 
     * @var Blockchain
     */
    protected $blockchain;

    const BLOCKCHAIN_PROVIDER_URL = 'http://127.0.0.1:8545';
    const BLOCKCHAIN_WRONG_PROVIDER_URL = 'http://not-exist-url-149719.com:1234';
    const ERC20_TOKEN_ADDRESS = '0x5fbdb2315678afecb367f032d93f642f64180aa3';
    const ERC20_TOKEN_ADDRESS2 = '0xe7f1725e7734ce288f8367e1bb143e90bb3f0512';
    const ERC20_TOKEN_OWNER_ADDRESS = '0xf39fd6e51aad88f6f4ce6ab8827279cfffb92266';
    const ERC20_TOKEN_OWNER_PK = 'ac0974bec39a17e36ba4a6b4d238ff944bacb478cbed5efcae784d7bf4f2ff80';

    /**
     * setUp
     * 
     * @return void
     */
    public function setUp(): void
    {
        $provider_url = self::BLOCKCHAIN_PROVIDER_URL; // localhost Ethereum node
        $options = [
            'gas_limit' => 200000,
            'max_gas_price' => 1.0 / 1000000000,
            'network_timeout' => 10,
        ];

        $this->blockchain = new Blockchain($provider_url, $options);
    }

    /**
     * tearDown
     * 
     * @return void
     */
    public function tearDown(): void {}
}
