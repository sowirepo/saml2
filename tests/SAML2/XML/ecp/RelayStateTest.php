<?php

declare(strict_types=1);

namespace SimpleSAML\Test\SAML2\XML\ecp;

use DOMDocument;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Test\XML\SerializableXMLTestTrait;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\XML\ecp\RelayState;
use SimpleSAML\XML\DOMDocumentFactory;
use SimpleSAML\XML\Exception\MissingAttributeException;

use function dirname;
use function strval;

/**
 * @package simplesamlphp/saml2
 * @covers \SimpleSAML\SAML2\XML\ecp\AbstractEcpElement
 * @covers \SimpleSAML\SAML2\XML\ecp\RelayState
 */
final class RelayStateTest extends TestCase
{
    use SerializableXMLTestTrait;


    /**
     */
    public function setUp(): void
    {
        $this->testedClass = RelayState::class;

        $this->xmlRepresentation = DOMDocumentFactory::fromFile(
            dirname(dirname(dirname(dirname(__FILE__)))) . '/resources/xml/ecp_RelayState.xml'
        );
    }


    /**
     */
    public function testMarshalling(): void
    {
        $rs = new RelayState('AGDY854379dskssda', false);

        $this->assertEquals(
            $this->xmlRepresentation->saveXML($this->xmlRepresentation->documentElement),
            strval($rs)
        );
    }


    /**
     */
    public function testUnmarshalling(): void
    {
        $rs = RelayState::fromXML($this->xmlRepresentation->documentElement);
        $this->assertEquals('AGDY854379dskssda', $rs->getContent());

        $this->assertEquals($this->xmlRepresentation->saveXML($this->xmlRepresentation->documentElement), strval($rs));
    }


    /**
     */
    public function testUnmarshallingWithMissingActorThrowsException(): void
    {
        $document = $this->xmlRepresentation->documentElement;
        $document->removeAttributeNS(C::NS_SOAP, 'actor');

        $this->expectException(MissingAttributeException::class);
        $this->expectExceptionMessage('Missing SOAP-ENV:actor attribute in <ecp:RelayState>.');

        RelayState::fromXML($document);
    }
}
