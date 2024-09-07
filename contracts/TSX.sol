// SPDX-License-Identifier: SEE LICENSE IN LICENSE
pragma solidity ^0.8.24;

import "@openzeppelin/contracts/token/ERC20/ERC20.sol";

contract TSX is ERC20 {
    constructor() ERC20("TSX", "TSX") {
        _mint(msg.sender, 1000_000_000000000_000000000);
    }
}