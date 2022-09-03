<?php

declare(strict_types=1);

namespace SimpleSAML\SAML2\XML\ecp;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\Exception\ProtocolViolationException;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingAttributeException;

use function boolval;
use function is_null;
use function strval;

/**
 * Class representing the ECP RequestAuthenticated element.
 *
 * @package simplesamlphp/saml2
 */
final class RequestAuthenticated extends AbstractEcpElement
{
    /** @var boolean|null $mustUnderstand */
    protected ?bool $mustUnderstand = null;


    /**
     * Create a ECP RequestAuthenticated element.
     *
     * @param boolean $mustUnderstand
     */
    public function __construct(?bool $mustUnderstand = null)
    {
        $this->mustUnderstand = $mustUnderstand;
    }


    /**
     * Convert XML into a RequestAuthenticated
     *
     * @param \DOMElement $xml The XML element we should load
     * @return self
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException if the qualified name of the supplied element is wrong
     * @throws \SimpleSAML\XML\Exception\MissingAttributeException if the supplied element is missing any of the mandatory attributes
     */
    public static function fromXML(DOMElement $xml): object
    {
        Assert::same($xml->localName, 'RequestAuthenticated', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, RequestAuthenticated::NS, InvalidDOMElementException::class);

        // Assert required attributes
        Assert::true(
            $xml->hasAttributeNS(C::NS_SOAP, 'actor'),
            'Missing SOAP-ENV:actor attribute in <ecp:RequestAuthenticated>.',
            MissingAttributeException::class
        );

        $mustUnderstand = $xml->getAttributeNS(C::NS_SOAP, 'mustUnderstand');
        $actor = $xml->getAttributeNS(C::NS_SOAP, 'actor');

        Assert::nullOrOneOf(
            $mustUnderstand,
            ['', '0', '1'],
            'Invalid value of SOAP-ENV:mustUnderstand attribute in <ecp:Response>.',
            ProtocolViolationException::class,
        );
        Assert::same(
            $actor,
            'http://schemas.xmlsoap.org/soap/actor/next',
            'Invalid value of SOAP-ENV:actor attribute in <ecp:Response>.',
            ProtocolViolationException::class,
        );

        $mustUnderstand = $mustUnderstand === null ? null : boolval($mustUnderstand);

        return new self($mustUnderstand);
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

        if (!is_null($this->mustUnderstand)) {
            $response->setAttributeNS(C::NS_SOAP, 'SOAP-ENV:mustUnderstand', $this->mustUnderstand ? '1' : '0');
        }
        $response->setAttributeNS(C::NS_SOAP, 'SOAP-ENV:actor', 'http://schemas.xmlsoap.org/soap/actor/next');

        return $response;
    }
}
