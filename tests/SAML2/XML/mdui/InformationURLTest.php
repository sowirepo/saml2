<?php

declare(strict_types=1);

namespace SimpleSAML\Test\SAML2\XML\md;

use DOMDocument;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Assert\AssertionFailedException;
use SimpleSAML\SAML2\Constants;
use SimpleSAML\SAML2\XML\md\AbstractLocalizedName;
use SimpleSAML\SAML2\XML\mdui\InformationURL;
use SimpleSAML\XML\DOMDocumentFactory;

/**
 * Tests for localized names.
 *
 * @covers \SimpleSAML\SAML2\XML\mdui\InformationURL
 * @covers \SimpleSAML\SAML2\XML\md\AbstractLocalizedName
 * @covers \SimpleSAML\SAML2\XML\md\AbstractMdElement
 * @package simplesamlphp/saml2
 */
final class InformationURLTest extends TestCase
{
    /** @var \DOMDocument */
    protected DOMDocument $document;


    /**
     */
    protected function setUp(): void
    {
        $this->document = DOMDocumentFactory::fromFile(
            dirname(dirname(dirname(dirname(__FILE__)))) . '/resources/xml/mdui_InformationURL.xml'
        );
    }


    // test marshalling


    /**
     * Test creating a InformationURL object from scratch.
     */
    public function testMarshalling(): void
    {
        $name = new InformationURL('en', 'http://www.example.edu/en/');

        $this->assertEquals('en', $name->getLanguage());
        $this->assertEquals('http://www.example.edu/en/', $name->getValue());

        $this->assertEquals($this->document->saveXML($this->document->documentElement), strval($name));
    }


    /**
     * Test that creating a InformationURL from scratch with an empty language fails.
     */
    public function testMarshallingWithEmptyLang(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('xml:lang cannot be empty.');

        new InformationURL('', 'http://www.example.edu/en/');
    }


    /**
     * Test that creating a InformationURL from scratch with an empty value works.
     */
    public function testMarshallingWithEmptyValue(): void
    {
        $name = new InformationURL('en', '');

        $this->document->documentElement->textContent = '';

        $this->assertEquals($this->document->saveXML($this->document->documentElement), strval($name));
    }


    // test unmarshalling


    /**
     * Test creating a InformationURL from XML.
     */
    public function testUnmarshalling(): void
    {
        $name = InformationURL::fromXML($this->document->documentElement);
        $this->assertEquals($this->document->saveXML($this->document->documentElement), strval($name));
    }


    /**
     * Test that creating a InformationURL from XML fails when xml:lang is missing.
     */
    public function testUnmarshallingWithoutLang(): void
    {
        $this->document->documentElement->removeAttributeNS(InformationURL::XML_NS, 'lang');

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Missing xml:lang from InformationURL');

        InformationURL::fromXML($this->document->documentElement);
    }


    /**
     * Test that creating a InformationURL from XML fails when xml:lang is empty.
     */
    public function testUnmarshallingWithEmptyLang(): void
    {
        $this->document->documentElement->setAttributeNS(InformationURL::XML_NS, 'lang', '');

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('xml:lang cannot be empty.');

        InformationURL::fromXML($this->document->documentElement);
    }


    /**
     * Test that creating a InformationURL from XML works for empty values.
     */
    public function testUnmarshallingWithEmptyValue(): void
    {
        $this->document->documentElement->textContent = '';
        $name = InformationURL::fromXML($this->document->documentElement);

        $this->assertEquals('en', $name->getLanguage());
        $this->assertEquals('', $name->getValue());
        $this->assertEquals($this->document->saveXML($this->document->documentElement), strval($name));
    }


    /**
     * Test that creating a InformationURL with an invalid url throws an exception
     */
    public function testUnmarshallingFailsInvalidURL(): void
    {
        $document = $this->document;
        $document->documentElement->textContent = 'this is no url';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('mdui:InformationURL is not a valid URL.');
        InformationURL::fromXML($document->documentElement);
    }


    /**
     * Test serialization / unserialization.
     */
    public function testSerialization(): void
    {
        $this->assertEquals(
            $this->document->saveXML($this->document->documentElement),
            strval(unserialize(serialize(InformationURL::fromXML($this->document->documentElement))))
        );
    }
}