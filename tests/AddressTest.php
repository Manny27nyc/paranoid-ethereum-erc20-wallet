<?php

declare(strict_types=1);

use Paranoid\Address;
use Paranoid\NativeCoin;

final class AddressTest extends \Tests\TestCaseBase
{
    public function testConstructEmptyKey(): void
    {
        $address = '';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Bad address provided:' . $address);
        new Address($address);
    }

    public function testConstruct(): void
    {
        $address = new Address('0x8626f6940E2eb28930eFb4CeF49B2d1F2C9C1199');
        $this->assertEquals('0x8626f6940E2eb28930eFb4CeF49B2d1F2C9C1199', $address->get_address());
    }

    public function testConstructLowerCase(): void
    {
        $address = new Address('0x8626f6940e2eb28930efb4cef49b2d1f2c9c1199');
        $this->assertEquals('0x8626f6940E2eb28930eFb4CeF49B2d1F2C9C1199', $address->get_address());
    }

    public function testConstructBadAddress1(): void
    {
        $address = '0x';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Bad address provided:' . $address);
        new Address($address);
    }

    public function testConstructBadAddress2(): void
    {
        $address = '0x8626f6940e2eb28930efb4cef49b2d1f2c9c11991';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Bad address provided:' . $address);
        new Address($address);
    }

    public function testConstructBadAddress3(): void
    {
        $address = '0x8626f6940e2eb28930efb4cef49b2d1f2c9c119';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Bad address provided:' . $address);
        new Address($address);
    }

    public function testConstructBadAddress4(): void
    {
        $address = '0x8626f6940e2eb28930efb4cef49b.d1f2c9c1199';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Bad address provided:' . $address);
        new Address($address);
    }

    public function testConstructBadAddress5(): void
    {
        $address = '0x8626f6940e2eb28930efbxcef49b2d1f2c9c1199';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Bad address provided:' . $address);
        new Address($address);
    }
}
