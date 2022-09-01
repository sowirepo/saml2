<?php

declare(strict_types=1);

namespace SimpleSAML\SAML2\XML\ecp;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\Exception\ProtocolViolationException;
use SimpleSAML\SAML2\XML\saml\SubjectConfirmationData;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingAttributeException;
use SimpleSAML\XML\Exception\TooManyElementsException;

use function is_null;
use function is_numeric;
use function strval;

/**
 * Class representing the ECP SubjectConfirmation element.
 *
 * @package simplesamlphp/saml2
 */
final class SubjectConfirmation extends \SimpleSAML\SAML2\XML\saml\SubjectConfirmation
{
    /** @var string */
    public const NS = C::NS_ECP;

    /** @var string */
    public const NS_PREFIX = 'ecp';


    /**
     * Create a ECP RequestAuthenticated element.
     *
     * @param string $method
     * @param \SimpleSAML\SAML2\XML\saml\SubjectConfirmationData|null $subjectConfirmationData
     */
    public function __construct(string $method, ?SubjectConfirmationData $subjectConfirmationData = null)
    {
        parent::__construct($method, null, $subjectConfirmationData);
    }


    /**
     * Convert XML into a SubjectConfirmation
     *
     * @param \DOMElement $xml The XML element we should load
     * @return self
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException if the qualified name of the supplied element is wrong
     * @throws \SimpleSAML\XML\Exception\MissingAttributeException if the supplied element is missing any of the mandatory attributes
     */
    public static function fromXML(DOMElement $xml): object
    {
        Assert::same($xml->localName, 'SubjectConfirmation', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, SubjectConfirmation::NS, InvalidDOMElementException::class);

        // Assert required attributes
        Assert::true(
            $xml->hasAttributeNS(C::NS_SOAP, 'actor'),
            'Missing SOAP-ENV:actor attribute in <ecp:SubjectConfirmation>.',
            MissingAttributeException::class
        );

        $mustUnderstand = $xml->getAttributeNS(C::NS_SOAP, 'mustUnderstand');
        $actor = $xml->getAttributeNS(C::NS_SOAP, 'actor');

        Assert::same(
            $mustUnderstand,
            '1',
            'Invalid value of SOAP-ENV:mustUnderstand attribute in <ecp:SubjectConfirmation>.',
            ProtocolViolationException::class,
        );
        Assert::same(
            $actor,
            'http://schemas.xmlsoap.org/soap/actor/next',
            'Invalid value of SOAP-ENV:actor attribute in <ecp:SubjectConfirmation>.',
            ProtocolViolationException::class,
        );

        $subjectConfirmationData = SubjectConfirmationData::getChildrenOfClass($xml);
        Assert::maxCount($subjectConfirmationData, 1, TooManyElementsException::class);

        return new self(
            self::getAttribute($xml, 'Method'),
            array_pop($subjectConfirmationData),
        );
    }


    /**
     * Convert this ECP RequestAuthentication to XML.
     *
     * @param \DOMElement|null $parent The element we should append this element to.
     * @return \DOMElement
     */
    public function toXML(DOMElement $parent = null): DOMElement
    {
        $response = parent::toXML($parent);

        $response->setAttributeNS(C::NS_SOAP, 'SOAP-ENV:mustUnderstand', '1');
        $response->setAttributeNS(C::NS_SOAP, 'SOAP-ENV:actor', 'http://schemas.xmlsoap.org/soap/actor/next');

        return $response;
    }
}
