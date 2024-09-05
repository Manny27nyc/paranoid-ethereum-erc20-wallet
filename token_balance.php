<?php
require './vendor/autoload.php';
require './blockchain.php';

use Paranoid\ERC20;
use Paranoid\Address;

try {
    $tokens_contract_address = new Address('0xdac17f958d2ee523a2206206994597c13d831ec7'); // USDT on Ethereum mainnet
    $user_account_address = new Address('0x476Bb28Bc6D0e9De04dB5E19912C392F9a76535d');

    $blockchain = init_blockchain();
    $token = new ERC20($tokens_contract_address, $blockchain);

    $tokens_balance = $token->get_address_balance($user_account_address);

    echo 'Tokens contract address: ' . $tokens_contract_address->get_address() . "\n";
    echo 'User account address: ' . $user_account_address->get_address() . "\n";
    echo 'Tokens balance: ' . $tokens_balance->get_wei_str() . ' wei' . "\n";
} catch (\Exception $ex) {
    echo $ex->getMessage() . "\n\n";
    echo "Stack trace:\n" . $ex->getTraceAsString() . "\n";
}
