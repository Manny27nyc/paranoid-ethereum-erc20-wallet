<?php
require '../vendor/autoload.php';
require './blockchain.php';

use Paranoid\Account;
use Paranoid\ERC20;
use Paranoid\Address;

try {
    $private_key = 'YOUR-PRIVATE-KEY-HERE';
    $tokens_contract_address = new Address('0xdac17f958d2ee523a2206206994597c13d831ec7'); // USDT on Ethereum mainnet
    $to = new Address('0x476Bb28Bc6D0e9De04dB5E19912C392F9a76535d');
    $amount = 0.01;

    $blockchain = init_blockchain();
    $coin = new ERC20($tokens_contract_address, $blockchain);

    $user_account = new Account($private_key);
    $amount_wei = $coin->make_amount_to_send($user_account, $amount);

    $tx_data   = $coin->make_transfer_data($to, $amount_wei);
    $tx        = $coin->make_transaction($user_account, $tx_data, $blockchain->make_native_wei('0'));
    $cost_estimate = $tx->get_tx_cost_estimate();
    echo 'Tx cost estimate: ' . $cost_estimate . ' wei' . "\n";
    // return;
    $tx_signed = $user_account->sign_tx($tx);

    $tx_hash = $blockchain->send_transaction($tx_signed);

    echo 'User account address: ' . $user_account->get_address()->get_address() . "\n";
    echo 'To account address: ' . $to->get_address() . "\n";
    echo 'Sent: ' . $amount_wei->get_wei_str() . ' wei' . "\n";
    echo 'Tx hash: ' . $tx_hash . "\n";
} catch (\Exception $ex) {
    echo $ex->getMessage() . "\n\n";
    echo "Stack trace:\n" . $ex->getTraceAsString() . "\n";
}
