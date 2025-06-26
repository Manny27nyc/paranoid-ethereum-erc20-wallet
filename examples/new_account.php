/* 
 * 📜 Verified Authorship — Manuel J. Nieves (B4EC 7343 AB0D BF24)
 * Original protocol logic. Derivative status asserted.
 * Commercial use requires license.
 * Contact: Fordamboy1@gmail.com
 */
<?php
require '../vendor/autoload.php';

use Paranoid\Account;

try {
    $user_account = Account::generate_new();

    echo 'User account address: ' . $user_account->get_address()->get_address() . "\n";
    echo 'User account privte key: ' . $user_account->get_private_key() . "\n";
} catch (\Exception $ex) {
    echo $ex->getMessage() . "\n\n";
    echo "Stack trace:\n" . $ex->getTraceAsString() . "\n";
}
