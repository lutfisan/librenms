<?php

namespace LibreNMS\Tests\Unit\Util;

use LibreNMS\Tests\TestCase;
use LibreNMS\Util\Mac;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class MacUtilTest extends TestCase
{
    public function testMacOutput(): void
    {
        $mac = Mac::parse('DeadBeefa0c3');
        $this->assertTrue($mac->isValid());
        $this->assertEquals('de:ad:be:ef:a0:c3', $mac->readable());
        $this->assertEquals('deadbeefa0c3', $mac->hex());
        $this->assertEquals('222.173.190.239.160.195', $mac->oid());
        $this->assertEquals(['de', 'ad', 'be', 'ef', 'a0', 'c3'], $mac->array());
    }

    public function testPartial(): void
    {
        $this->assertEquals('00000c000000', Mac::parsePartial('0000.0c')->hex());
        $this->assertEquals('080020000000', Mac::parsePartial('08:00:20')->hex());
        $this->assertEquals('080020000000', Mac::parsePartial('8::20')->hex());
        $this->assertEquals('aaaaaa000000', Mac::parsePartial('aaaaaa')->hex());
        $this->assertEquals('aaaaa0000000', Mac::parsePartial('aaaaa')->hex());
        $this->assertEquals('aaaaaa120000', Mac::parsePartial('aaaaaa12')->hex());
        $this->assertEquals('aaaaaa123000', Mac::parsePartial('aaaaaa123')->hex());
    }

    public function testBridgeParsing(): void
    {
        $this->assertEquals('0c85255ce500', Mac::parseBridge('80 62 0c 85 25 5c e5 00')->hex());
        $this->assertEquals('000000000001', Mac::parseBridge('00 00 00 00 00 00 00 01 ')->hex());
        $this->assertEquals('000000000002', Mac::parseBridge('0-00.00.00.00.00.02')->hex());
        $this->assertEquals('00186e6449a0', Mac::parseBridge('0:18:6e:64:49:a0')->hex());
        $this->assertEquals('0c85255ce500', Mac::parseBridge('80620c85255ce500')->hex());
    }

    #[Test]
    #[DataProvider('validMacProvider')]
    public function testMacToHex(string $from, string $to): void
    {
        $this->assertEquals($to, Mac::parse($from)->hex());
    }

    public static function validMacProvider(): array
    {
        return [
            ['00:00:00:00:00:01', '000000000001'],
            ['00-00-00-00-00-01', '000000000001'],
            ['000000.000001',     '000000000001'],
            ['000000000001',      '000000000001'],
            ['00:12:34:ab:cd:ef', '001234abcdef'],
            ['00:12:34:AB:CD:EF', '001234abcdef'],
            ['0:12:34:AB:CD:EF',  '001234abcdef'],
            ['00-12-34-AB-CD-EF', '001234abcdef'],
            ['001234-ABCDEF',     '001234abcdef'],
            ['0012.34AB.CDEF',    '001234abcdef'],
            ['00:02:04:0B:0D:0F', '0002040b0d0f'],
            ['0:2:4:B:D:F',       '0002040b0d0f'],
            ['0:2:4:B:D:F',       '0002040b0d0f'],
            ['00d9.d110.21f9',    '00d9d11021f9'],
            ['garbage',           ''],
        ];
    }
}
