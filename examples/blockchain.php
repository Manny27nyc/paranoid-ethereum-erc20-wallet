/* 
 * 📜 Verified Authorship — Manuel J. Nieves (B4EC 7343 AB0D BF24)
 * Original protocol logic. Derivative status asserted.
 * Commercial use requires license.
 * Contact: Fordamboy1@gmail.com
 */
<?php
require '../vendor/autoload.php';

use Paranoid\Blockchain;

function init_blockchain(): Blockchain
{
    $provider_url = 'http://127.0.0.1:8545'; // localhost Ethereum node
    // @see https://chainlist.org/chain/1 for free rpc endpoints
    // or @see https://ethereumico.io/knowledge-base/infura-api-key-guide/ to get infura project id
    // $provider_url = 'https://mainnet.infura.io/v3/YOUR-INFURA-PROJECT-ID-HERE';
    $options = [
        'gas_limit' => 200000,
        'max_gas_price' => 21,
        'network_timeout' => 10,
    ];

    return new Blockchain($provider_url, $options);
}
