<?php

declare(strict_types=1);

namespace SimpleSAML\SAML2\XML\md;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\SAML2\Compat\ContainerSingleton;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\Utils;
use SimpleSAML\SAML2\XML\ExtensionPointInterface;
use SimpleSAML\SAML2\XML\ExtensionPointTrait;
use SimpleSAML\XML\Chunk;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\Exception\TooManyElementsException;
use SimpleSAML\XML\Utils as XMLUtils;
use SimpleSAML\XMLSecurity\Backend\EncryptionBackend;
use SimpleSAML\XMLSecurity\XML\EncryptableElementInterface;
use SimpleSAML\XMLSecurity\XML\EncryptableElementTrait;

use function array_pop;
use function count;
use function explode;

/**
 * SAML Metadata RoleDescriptor element.
 *
 * @package simplesamlphp/saml2
 */
abstract class AbstractRoleDescriptor extends AbstractRoleDescriptorType implements ExtensionPointInterface
{
    use ExtensionPointTrait;

    /** @var string */
    public const LOCALNAME = 'RoleDescriptor';

    /** @var string */
    protected string $type;


    /**
     * Initialize a saml:RoleDescriptor from scratch
     *
     * @param string $type
     * @param string[] $protocolSupportEnumeration A set of URI specifying the protocols supported.
     * @param string|null $ID The ID for this document. Defaults to null.
     * @param int|null $validUntil Unix time of validity for this document. Defaults to null.
     * @param string|null $cacheDuration Maximum time this document can be cached. Defaults to null.
     * @param \SimpleSAML\SAML2\XML\md\Extensions|null $extensions An Extensions object. Defaults to null.
     * @param string|null $errorURL An URI where to redirect users for support. Defaults to null.
     * @param \SimpleSAML\SAML2\XML\md\KeyDescriptor[] $keyDescriptor
     *   An array of KeyDescriptor elements. Defaults to an empty array.
     * @param \SimpleSAML\SAML2\XML\md\Organization|null $organization
     *   The organization running this entity. Defaults to null.
     * @param \SimpleSAML\SAML2\XML\md\ContactPerson[] $contactPerson
     *   An array of contacts for this entity. Defaults to an empty array.
     * @param \DOMAttr[] $namespacedAttributes
     */
    protected function __construct(
        string $type,
        array $protocolSupportEnumeration,
        ?string $ID = null,
        ?int $validUntil = null,
        ?string $cacheDuration = null,
        ?Extensions $extensions = null,
        ?string $errorURL = null,
        array $keyDescriptor = [],
        ?Organization $organization = null,
        array $contactPerson = [],
        array $namespacedAttributes = [],
    ) {
        parent::__construct(
            $protocolSupportEnumeration,
            $ID,
            $validUntil,
            $cacheDuration,
            $extensions,
            $errorURL,
            $keyDescriptors,
            $organization,
            $contacts,
            $namespacedAttributes
        );

        $this->type = $type;
    }


    /**
     * @inheritDoc
     */
    public function getXsiType(): string
    {
        return $this->type;
    }


    /**
     * Convert XML into an RoleDescriptor
     *
     * @param \DOMElement $xml The XML element we should load
     * @return \SimpleSAML\SAML2\XML\md\AbstractRoleDescriptor
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException if the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'RoleDescriptor', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, C::NS_MD, InvalidDOMElementException::class);
        Assert::true(
            $xml->hasAttributeNS(C::NS_XSI, 'type'),
            'Missing required xsi:type in <saml:RoleDescriptor> element.',
            SchemaViolationException::class
        );

        $type = $xml->getAttributeNS(C::NS_XSI, 'type');
        Assert::validQName($type, SchemaViolationException::class);

        // first, try to resolve the type to a full namespaced version
        $qname = explode(':', $type, 2);
        if (count($qname) === 2) {
            list($prefix, $element) = $qname;
        } else {
            $prefix = null;
            list($element) = $qname;
        }
        $ns = $xml->lookupNamespaceUri($prefix);
        $type = ($ns === null ) ? $element : implode(':', [$ns, $element]);

        // now check if we have a handler registered for it
        $handler = Utils::getContainer()->getExtensionHandler($type);
        if ($handler === null) {
            // we don't have a handler, proceed with unknown RoleDescriptor
            $protocols = self::getAttribute($xml, 'protocolSupportEnumeration');

            $validUntil = self::getAttribute($xml, 'validUntil', null);
            $orgs = Organization::getChildrenOfClass($xml);
            Assert::maxCount($orgs, 1, 'More than one Organization found in this descriptor', TooManyElementsException::class);

            $extensions = Extensions::getChildrenOfClass($xml);
            Assert::maxCount($extensions, 1, 'Only one md:Extensions element is allowed.', TooManyElementsException::class);

            return new UnknownRoleDescriptor(
                new Chunk($xml),
                $type,
                preg_split('/[\s]+/', trim($protocols)),
                self::getAttribute($xml, 'ID', null),
                $validUntil !== null ? XMLUtils::xsDateTimeToTimestamp($validUntil) : null,
                self::getAttribute($xml, 'cacheDuration', null),
                array_pop($extensions),
                self::getAttribute($xml, 'errorURL', null),
                KeyDescriptor::getChildrenOfClass($xml),
                array_pop($orgs),
                ContactPerson::getChildrenOfClass($xml),
            );
        }

        Assert::subclassOf(
            $handler,
            AbstractRoleDescriptor::class,
            'Elements implementing RoleDescriptor must extend \SimpleSAML\SAML2\XML\saml\AbstractRoleDescriptor.',
        );

        return $handler::fromXML($xml);
    }


    /**
     * Convert this RoleDescriptor to XML.
     *
     * @param \DOMElement|null $parent The element we are converting to XML.
     * @return \DOMElement The XML element after adding the data corresponding to this RoleDescriptor.
     */
    public function toUnsignedXML(?DOMElement $parent = null): DOMElement
    {
        $e = parent::toUnsignedXML($parent);

        $xsiType = $e->ownerDocument->createAttributeNS(C::NS_XSI, 'xsi:type');
        $xsiType->value = $this->getXsiType();

        $e->setAttributeNodeNS($xsiType);
        $e->setAttribute('xmlns:' . static::getXsiTypePrefix(), static::getXsiTypeNamespaceURI());

        return $e;
    }
}
