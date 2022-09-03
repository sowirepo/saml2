<?php

declare(strict_types=1);

namespace SimpleSAML\SAML2\XML\ecp;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\Exception\ProtocolViolationException;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingAttributeException;
use SimpleSAML\XML\XMLStringElementTrait;

use function boolval;
use function strval;

/**
 * Class representing the ECP RelayState element.
 *
 * @package simplesamlphp/saml2
 */
final class RelayState extends AbstractEcpElement
{
    use XMLStringElementTrait;

    /**
     * Create a ECP RelayState element.
     *
     * @param string $content
     * @param boolean $mustUnderstand
     */
    public function __construct(string $content, bool $mustUnderstand)
    {
        $this->setContent($content);
    }


    /**
     * Convert XML into a RelayState
     *
     * @param \DOMElement $xml The XML element we should load
     * @return self
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException if the qualified name of the supplied element is wrong
     * @throws \SimpleSAML\XML\Exception\MissingAttributeException if the supplied element is missing any of the mandatory attributes
     */
    public static function fromXML(DOMElement $xml): object
    {
        Assert::same($xml->localName, 'RelayState', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, RelayState::NS, InvalidDOMElementException::class);

        // Assert required attributes
        Assert::true(
            $xml->hasAttributeNS(C::NS_SOAP, 'actor'),
            'Missing SOAP-ENV:actor attribute in <ecp:RelayState>.',
            MissingAttributeException::class
        );
        Assert::true(
            $xml->hasAttributeNS(C::NS_SOAP, 'mustUnderstand'),
            'Missing SOAP-ENV:mustUnderstalnd attribute in <ecp:RelayState>.',
            MissingAttributeException::class
        );

        $mustUnderstand = $xml->getAttributeNS(C::NS_SOAP, 'mustUnderstand');
        $actor = $xml->getAttributeNS(C::NS_SOAP, 'actor');

        Assert::same(
            $mustUnderstand,
            '1',
            'Invalid value of SOAP-ENV:mustUnderstand attribute in <ecp:Response>.',
            ProtocolViolationException::class,
        );
        Assert::same(
            $actor,
            'http://schemas.xmlsoap.org/soap/actor/next',
            'Invalid value of SOAP-ENV:actor attribute in <ecp:Response>.',
            ProtocolViolationException::class,
        );

        return new self($xml->textContent, boolval($mustUnderstand));
    }


    /**
     * Convert this ECP RequestAuthentication to XML.
     *
     * @param \DOMElement|null $parent The element we should append this element to.
     * @return \DOMElement
     */
    public function toXML(DOMElement $parent = null): DOMElement
    {
        $response = $this->instantiateParentElement($parent);
        $response->textContent = $this->getContent();

        $response->setAttributeNS(C::NS_SOAP, 'SOAP-ENV:mustUnderstand', '1');
        $response->setAttributeNS(C::NS_SOAP, 'SOAP-ENV:actor', 'http://schemas.xmlsoap.org/soap/actor/next');

        return $response;
    }
}
