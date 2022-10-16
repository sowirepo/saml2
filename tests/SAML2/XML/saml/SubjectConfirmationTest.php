<?php

declare(strict_types=1);

namespace SimpleSAML\Test\SAML2\XML\saml;

use PHPUnit\Framework\TestCase;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\Utils\XPath;
use SimpleSAML\SAML2\XML\saml\NameID;
use SimpleSAML\SAML2\XML\saml\SubjectConfirmation;
use SimpleSAML\SAML2\XML\saml\SubjectConfirmationData;
use SimpleSAML\Test\XML\SerializableElementTestTrait;
use SimpleSAML\XML\DOMDocumentFactory;
use SimpleSAML\XML\Exception\MissingAttributeException;
use SimpleSAML\XML\Exception\TooManyElementsException;

use function dirname;
use function strval;

/**
 * Class \SAML2\XML\saml\SubjectConfirmationTest
 *
 * @covers \SimpleSAML\SAML2\XML\saml\SubjectConfirmation
 * @covers \SimpleSAML\SAML2\XML\saml\AbstractSamlElement
 * @package simplesamlphp/saml2
 */
final class SubjectConfirmationTest extends TestCase
{
    use SerializableElementTestTrait;


    public function setup(): void
    {
        $this->testedClass = SubjectConfirmation::class;

        $this->xmlRepresentation = DOMDocumentFactory::fromFile(
            dirname(dirname(dirname(dirname(__FILE__)))) . '/resources/xml/saml_SubjectConfirmation.xml'
        );
    }


    // marshalling


    /**
     */
    public function testMarshalling(): void
    {
        $subjectConfirmation = new SubjectConfirmation(
            'urn:test:SomeMethod',
            new NameID('SomeNameIDValue'),
            new SubjectConfirmationData()
        );

        $this->assertEquals(
            $this->xmlRepresentation->saveXML($this->xmlRepresentation->documentElement),
            strval($subjectConfirmation)
        );
    }


    /**
     */
    public function testMarshallingElementOrdering(): void
    {
        $subjectConfirmation = new SubjectConfirmation(
            'urn:test:SomeMethod',
            new NameID('SomeNameIDValue'),
            new SubjectConfirmationData()
        );

        // Marshall it to a \DOMElement
        $subjectConfirmationElement = $subjectConfirmation->toXML();

        // Test for a NameID
        $xpCache = XPath::getXPath($subjectConfirmationElement);
        $subjectConfirmationElements = XPath::xpQuery($subjectConfirmationElement, './saml_assertion:NameID', $xpCache);
        $this->assertCount(1, $subjectConfirmationElements);

        // Test ordering of SubjectConfirmation contents
        /** @psalm-var \DOMElement[] $subjectConfirmationElements */
        $subjectConfirmationElements = XPath::xpQuery(
            $subjectConfirmationElement,
            './saml_assertion:NameID/following-sibling::*',
            $xpCache
        );
        $this->assertCount(1, $subjectConfirmationElements);
        $this->assertEquals('saml:SubjectConfirmationData', $subjectConfirmationElements[0]->tagName);
    }


    // unmarshalling


    /**
     */
    public function testUnmarshalling(): void
    {
        $subjectConfirmation = SubjectConfirmation::fromXML($this->xmlRepresentation->documentElement);

        $this->assertEquals(
            $this->xmlRepresentation->saveXML($this->xmlRepresentation->documentElement),
            strval($subjectConfirmation)
        );
    }


    /**
     */
    public function testMethodMissingThrowsException(): void
    {
        $document = $this->xmlRepresentation->documentElement;
        $document->removeAttribute('Method');

        $this->expectException(MissingAttributeException::class);
        $this->expectExceptionMessage("Missing 'Method' attribute on saml:SubjectConfirmation.");

        SubjectConfirmation::fromXML($document);
    }


    /**
     */
    public function testManyNameIDThrowsException(): void
    {
        $samlNamespace = C::NS_SAML;
        $document = DOMDocumentFactory::fromString(<<<XML
<saml:SubjectConfirmation xmlns:saml="{$samlNamespace}" Method="urn:test:SomeMethod">
  <saml:NameID>SomeNameIDValue</saml:NameID>
  <saml:NameID>AnotherNameIDValue</saml:NameID>
  <saml:SubjectConfirmationData/>
</saml:SubjectConfirmation>
XML
        );

        $this->expectException(TooManyElementsException::class);
        $this->expectExceptionMessage('More than one <saml:NameID> in <saml:SubjectConfirmation>.');
        SubjectConfirmation::fromXML($document->documentElement);
    }


    /**
     * Test that creating a SubjectConfirmation fails with multiple identifiers of different types.
     */
    public function testMultipleIdentifiers(): void
    {
        $samlNamespace = C::NS_SAML;
        $document = DOMDocumentFactory::fromString(<<<XML
<saml:SubjectConfirmation xmlns:saml="{$samlNamespace}" Method="urn:test:SomeMethod">
  <saml:BaseID xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="someType">SomeNameIDValue</saml:BaseID>
  <saml:NameID>AnotherNameIDValue</saml:NameID>
  <saml:SubjectConfirmationData/>
</saml:SubjectConfirmation>
XML
        );

        $this->expectException(TooManyElementsException::class);
        $this->expectExceptionMessage(
            'A <saml:SubjectConfirmation> can contain exactly one of <saml:BaseID>, <saml:NameID> or <saml:EncryptedID>.'
        );
        SubjectConfirmation::fromXML($document->documentElement);
    }


    /**
     */
    public function testManySubjectConfirmationDataThrowsException(): void
    {
        $samlNamespace = C::NS_SAML;
        $document = DOMDocumentFactory::fromString(<<<XML
<saml:SubjectConfirmation xmlns:saml="{$samlNamespace}" Method="urn:test:SomeMethod">
  <saml:NameID>SomeNameIDValue</saml:NameID>
  <saml:SubjectConfirmationData Recipient="Me" />
  <saml:SubjectConfirmationData Recipient="Someone Else" />
</saml:SubjectConfirmation>
XML
        );

        $this->expectException(TooManyElementsException::class);
        $this->expectExceptionMessage(
            'More than one <saml:SubjectConfirmationData> in <saml:SubjectConfirmation>.'
        );
        SubjectConfirmation::fromXML($document->documentElement);
    }
}
