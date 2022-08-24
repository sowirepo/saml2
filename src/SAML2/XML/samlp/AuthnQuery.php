<?php

declare(strict_types=1);

namespace SimpleSAML\SAML2\XML\samlp;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\SAML2\Exception\Protocol\RequestVersionTooHighException;
use SimpleSAML\SAML2\Exception\Protocol\RequestVersionTooLowException;
use SimpleSAML\SAML2\Exception\ProtocolViolationException;
use SimpleSAML\SAML2\XML\saml\Issuer;
use SimpleSAML\SAML2\XML\saml\Subject;
use SimpleSAML\SAML2\XML\samlp\RequestedAuthnContext;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingElementException;
use SimpleSAML\XML\Exception\TooManyElementsException;
use SimpleSAML\XML\Utils as XMLUtils;
use SimpleSAML\XMLSecurity\ds\Signature;

use function array_pop;
use function in_array;

/**
 * Class for SAML 2 authentication query messages.
 *
 * The <AuthnQuery> message element is used to make the query What assertions containing
 * authentication statements are available for this subject? A successful <Response> will contain one or
 * more assertions containing authentication statements.
 *
 * The <AuthnQuery> message MUST NOT be used as a request for a new authentication using
 * credentials provided in the request. <AuthnQuery> is a request for statements about authentication acts
 * that have occurred in a previous interaction between the indicated subject and the authentication authority.
 *
 * @package simplesamlphp/saml2
 */
class AuthnQuery extends AbstractSubjectQuery
{
    /**
     * The requested authentication context.
     *
     * @var \SimpleSAML\SAML2\XML\samlp\RequestedAuthnContext|null
     */
    protected ?RequestedAuthnContext $requestedAuthnContext;

    /**
     * The session index
     *
     * @var string|null $sessionIndex
     */
    protected ?string $sessionIndex;


    /**
     * Constructor for SAML 2 AttributeQuery.
     *
     * @param \SimpleSAML\SAML2\XML\saml\Subject $subject
     * @param \SimpleSAML\SAML2\XML\samlp\RequestedAuthnContext|null $requestedAuthnContext
     * @param string|null $sessionIndex
     * @param \SimpleSAML\SAML2\XML\saml\Issuer $issuer
     * @param string $id
     * @param int $issueInstant
     * @param string|null $destination
     * @param string|null $consent
     * @param \SimpleSAML\SAML2\XML\samlp\Extensions $extensions
     */
    public function __construct(
        Subject $subject,
        ?Issuer $issuer = null,
        ?RequestedAuthnContext $requestedAuthnContext = null,
        ?string $sessionIndex = null,
        ?string $id = null,
        ?int $issueInstant = null,
        ?string $destination = null,
        ?string $consent = null,
        ?Extensions $extensions = null
    ) {
        parent::__construct($subject, $issuer, $id, $issueInstant, $destination, $consent, $extensions);

        $this->setRequestedAuthnContext($requestedAuthnContext);
        $this->setSessionIndex($sesseionIndex);
    }


    /**
     * @return \SimpleSAML\SAML2\XML\samlp\RequestedAuthnContext|null
     */
    public function getRequestedAuthnContext(): ?RequestedAuthnContext
    {
        return $this->requestedAuthnContext;
    }


    /**
     * @param \SimpleSAML\SAML2\XML\samlp\RequestedAuthnContext|null $requestedAuthnContext
     */
    protected function setRequestedAuthnContext(?RequestedAuthnContext $requestedAuthnContext): void
    {
        $this->requestedAuthnContext = $requestedAuthnContext;
    }


    /**
     * Create a class from XML
     *
     * @param \DOMElement $xml
     * @return self
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException if the qualified name of the supplied element is wrong
     * @throws \SimpleSAML\XML\Exception\MissingAttributeException if the supplied element is missing one of the mandatory attributes
     * @throws \SimpleSAML\XML\Exception\MissingElementException if one of the mandatory child-elements is missing
     * @throws \SimpleSAML\XML\Exception\TooManyElementsException if too many child-elements of a type are specified
     */
    public static function fromXML(DOMElement $xml): object
    {
        Assert::same($xml->localName, 'AuthnQueryQuery', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, AuthnQuery::NS, InvalidDOMElementException::class);

        Assert::true(version_compare('2.0', self::getAttribute($xml, 'Version'), '<='), RequestVersionTooLowException::class);
        Assert::true(version_compare('2.0', self::getAttribute($xml, 'Version'), '>='), RequestVersionTooHighException::class);

        $id = self::getAttribute($xml, 'ID');
        $sessionIndex = self::getAttribute($xml, 'SessionIndex', null);
        $destination = self::getAttribute($xml, 'Destination', null);
        $consent = self::getAttribute($xml, 'Consent', null);

        $issueInstant = self::getAttribute($xml, 'IssueInstant');
        Assert::validDateTimeZulu($issueInstant, ProtocolViolationException::class);
        $issueInstant = XMLUtils::xsDateTimeToTimestamp($issueInstant);

        $requestedAuthnContext = RequestedAuthnContext::getChildrenOfClass($xml);
        Assert::countBetween($issuer, 0, 1);

        $issuer = Issuer::getChildrenOfClass($xml);
        Assert::countBetween($issuer, 0, 1);

        $extensions = Extensions::getChildrenOfClass($xml);
        Assert::maxCount($extensions, 1, 'Only one saml:Extensions element is allowed.', TooManyElementsException::class);

        $subject = Subject::getChildrenOfClass($xml);
        Assert::notEmpty($subject, 'Missing subject in subject query.', MissingElementException::class);
        Assert::maxCount($subject, 1, 'More than one <saml:Subject> in AttributeQuery', TooManyElementsException::class);

        $signature = Signature::getChildrenOfClass($xml);
        Assert::maxCount($signature, 1, 'Only one ds:Signature element is allowed.', TooManyElementsException::class);

        $request = new self(
            array_pop($subject),
            array_pop($requestedAuthnContext),
            $sessionIndex,
            array_pop($issuer),
            $id,
            $issueInstant,
            $destination,
            $consent,
            array_pop($extensions)
        );

        if (!empty($signature)) {
            $request->setSignature($signature[0]);
            $request->setXML($xml);
        }

        return $request;
    }


    /**
     * Convert this message to an unsigned XML document.
     * This method does not sign the resulting XML document.
     *
     * @return \DOMElement The root element of the DOM tree
     */
    protected function toUnsignedXML(?DOMElement $parent = null): DOMElement
    {
        $e = parent::toUnsignedXML($parent);

        if ($this->requestedAuthnContext !== null) {
            $this->requestedAuthnContext->toXML($e);
        }

        if ($this->sessionIndex !== null) {
            $e->setAttribute('SessionIndex', $this->sessionIndex);
        }

        return $e;
    }
}
