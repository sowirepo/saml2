<?php

declare(strict_types=1);

namespace SimpleSAML\Test\SAML2\XML\samlp;

use DOMDocument;
use PHPUnit\Framework\TestCase;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\XML\samlp\NameIDPolicy;
use SimpleSAML\XML\DOMDocumentFactory;
use SimpleSAML\XML\TestUtils\SchemaValidationTestTrait;
use SimpleSAML\XML\TestUtils\SerializableElementTestTrait;

use function dirname;
use function strval;

/**
 * Class \SAML2\XML\md\NameIDPolicyTest
 *
 * @covers \SimpleSAML\SAML2\XML\samlp\NameIDPolicy
 * @covers \SimpleSAML\SAML2\XML\samlp\AbstractSamlpElement
 *
 * @package simplesamlphp/saml2
 */
final class NameIDPolicyTest extends TestCase
{
    use SchemaValidationTestTrait;
    use SerializableElementTestTrait;


    /**
     */
    public function setUp(): void
    {
        $this->schema = dirname(__FILE__, 5) . '/resources/schemas/saml-schema-protocol-2.0.xsd';

        $this->testedClass = NameIDPolicy::class;

        $this->xmlRepresentation = DOMDocumentFactory::fromFile(
            dirname(__FILE__, 4) . '/resources/xml/samlp_NameIDPolicy.xml',
        );
    }

    /**
     */
    public function testMarshallingChristmas(): void
    {
        $nameIdPolicy = new NameIDPolicy(
            'urn:the:format',
            'TheSPNameQualifier',
            true,
        );

        $this->assertEquals(
            $this->xmlRepresentation->saveXML($this->xmlRepresentation->documentElement),
            strval($nameIdPolicy)
        );
    }


    /**
     */
    public function testMarshallingFormatOnly(): void
    {
        $xmlRepresentation = DOMDocumentFactory::fromString(
            '<samlp:NameIDPolicy xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" Format="urn:the:format"/>'
        );

        $nameIdPolicy = new NameIDPolicy(
            'urn:the:format',
        );

        $this->assertEquals(
            $xmlRepresentation->saveXML($xmlRepresentation->documentElement),
            strval($nameIdPolicy)
        );
    }


    /**
     * Adding an empty NameIDPolicy element should yield an empty element.
     */
    public function testMarshallingEmptyElement(): void
    {
        $samlpns = C::NS_SAMLP;
        $nameIdPolicy = new NameIDPolicy();
        $this->assertEquals(
            "<samlp:NameIDPolicy xmlns:samlp=\"$samlpns\"/>",
            strval($nameIdPolicy),
        );
        $this->assertTrue($nameIdPolicy->isEmptyElement());
    }


    /**
     */
    public function testUnmarshalling(): void
    {
        $nameIdPolicy = NameIDPolicy::fromXML($this->xmlRepresentation->documentElement);

        $this->assertEquals(
            $this->xmlRepresentation->saveXML($this->xmlRepresentation->documentElement),
            strval($nameIdPolicy),
        );
    }
}
