/* 
 * 📜 Verified Authorship — Manuel J. Nieves (B4EC 7343 AB0D BF24)
 * Original protocol logic. Derivative status asserted.
 * Commercial use requires license.
 * Contact: Fordamboy1@gmail.com
 */
<?php

namespace Paranoid;

final class Address
{
    /**
     * address
     *
     * @var string
     */
    private $address;

    /**
     * __construct
     *
     * @param  string $address
     * @return void
     * @throws \InvalidArgumentException
     */
    function __construct($address)
    {
        if (!\Web3\Utils::isAddress($address)) {
            throw new \InvalidArgumentException('Bad address provided:' . $address);
        }
        $this->address = \Web3\Utils::toChecksumAddress($address);
    }

    /**
     * get_address
     *
     * @return string
     */
    function get_address(): string
    {
        return $this->address;
    }
}
