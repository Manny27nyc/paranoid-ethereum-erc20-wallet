<?php
require './vendor/autoload.php';
require './blockchain.php';

use Paranoid\NativeCoin;
use Paranoid\Address;

try {
    $user_account_address = new Address('0x476Bb28Bc6D0e9De04dB5E19912C392F9a76535d');

    $blockchain = init_blockchain();
    $coin = new NativeCoin($blockchain);

    $balance = $coin->get_address_balance($user_account_address);

    echo 'User account address: ' . $user_account_address->get_address() . "\n";
    echo 'ETH balance: ' . $balance->get_wei_str() . ' wei' . "\n";
} catch (\Exception $ex) {
    echo $ex->getMessage() . "\n\n";
    echo "Stack trace:\n" . $ex->getTraceAsString() . "\n";
}
