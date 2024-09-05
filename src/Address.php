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
