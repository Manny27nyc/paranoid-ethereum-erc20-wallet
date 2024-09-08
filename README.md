# The Paranoid Ethereum Wallet library

[![PHP](https://github.com/olegabr/paranoid-ethereum-erc20-wallet/actions/workflows/php.yml/badge.svg)](https://github.com/olegabr/paranoid-ethereum-erc20-wallet/actions/workflows/php.yml)
[![codecov](https://codecov.io/gh/olegabr/paranoid-ethereum-erc20-wallet/branch/main/graph/badge.svg)](https://codecov.io/gh/olegabr/paranoid-ethereum-erc20-wallet)
[![Licensed under the MIT License](https://img.shields.io/badge/License-MIT-blue.svg)](https://github.com/olegabr/paranoid-ethereum-erc20-wallet/blob/main/LICENSE)

You can generate accounts, check balances and send native coins and tokens from your local console.
Designed to prevent all user errors while sending coins and tokens on blockchain.

> Double check variable values, especially the `to` and `tokens_contract_address` addresses before executing scripts.

## Install

`composer install`

> PHP 7.4 is required.

## Configuration

Set your values for the `$provider_url` and `$options` in the `./blockchain.php` file.

> See [chainlist.org](https://chainlist.org/chain/1) for free rpc endpoints or see the [Get Infura API Key Guide](https://ethereumico.io/knowledge-base/infura-api-key-guide/) to get infura project id.

## Create account

`php new_account.php`

## Check balance

Edit value of the `$user_account_address` variable and execute script:

`php eth_balance.php`

## Check token balance

Edit values of the `$tokens_contract_address` and `$user_account_address` variables and execute script:

`php token_balance.php`

## Send native coins

Edit values of the `$private_key`, `$to` and `amount` variables and execute script:

`php send_native.php`

## Send tokens

Edit values of the `$private_key`, `$tokens_contract_address`, `$to` and `amount` variables and execute script:

`php send_token.php`
