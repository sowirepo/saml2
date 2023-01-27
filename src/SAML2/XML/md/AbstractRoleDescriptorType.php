<?php

declare(strict_types=1);

namespace SimpleSAML\SAML2\XML\md;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\XML\Exception\SchemaViolationException;

use function implode;

/**
 * Class representing SAML 2 RoleDescriptorType.
 *
 * @package simplesamlphp/saml2
 */
abstract class AbstractRoleDescriptorType extends AbstractMetadataDocument
{
    /**
     * Initialize a RoleDescriptor.
     *
     * @param string[] $protocolSupportEnumeration A set of URI specifying the protocols supported.
     * @param string|null $ID The ID for this document. Defaults to null.
     * @param int|null $validUntil Unix time of validity for this document. Defaults to null.
     * @param string|null $cacheDuration Maximum time this document can be cached. Defaults to null.
     * @param \SimpleSAML\SAML2\XML\md\Extensions|null $extensions An Extensions object. Defaults to null.
     * @param string|null $errorURL An URI where to redirect users for support. Defaults to null.
     * @param \SimpleSAML\SAML2\XML\md\KeyDescriptor[] $keyDescriptors An array of KeyDescriptor elements. Defaults to an empty array.
     * @param \SimpleSAML\SAML2\XML\md\Organization|null $organization The organization running this entity. Defaults to null.
     * @param \SimpleSAML\SAML2\XML\md\ContactPerson[] $contacts An array of contacts for this entity. Defaults to an empty array.
     * @param \DOMAttr[] $namespacedAttributes
     */
    public function __construct(
        protected array $protocolSupportEnumeration,
        ?string $ID = null,
        ?int $validUntil = null,
        ?string $cacheDuration = null,
        ?Extensions $extensions = null,
        protected ?string $errorURL = null,
        protected array $keyDescriptors = [],
        protected ?Organization $organization = null,
        protected array $contacts = [],
        array $namespacedAttributes = []
    ) {
        Assert::minCount(
            $protocolSupportEnumeration,
            1,
            'At least one protocol must be supported by this ' . static::NS_PREFIX . ':' . static::getLocalName() . '.',
        );
        Assert::allValidURI($protocolSupportEnumeration, SchemaViolationException::class);
        Assert::nullOrValidURI($errorURL, SchemaViolationException::class); // Covers the empty string
        Assert::allIsInstanceOf(
            $contacts,
            ContactPerson::class,
            'All contacts must be an instance of md:ContactPerson',
        );
        Assert::allIsInstanceOf(
            $keyDescriptors,
            KeyDescriptor::class,
            'All key descriptors must be an instance of md:KeyDescriptor',
        );

        parent::__construct($ID, $validUntil, $cacheDuration, $extensions, $namespacedAttributes);
    }


    /**
     * Collect the value of the errorURL property.
     *
     * @return string|null
     */
    public function getErrorURL()
    {
        return $this->errorURL;
    }


    /**
     * Collect the value of the protocolSupportEnumeration property.
     *
     * @return string[]
     */
    public function getProtocolSupportEnumeration()
    {
        return $this->protocolSupportEnumeration;
    }


    /**
     * Collect the value of the Organization property.
     *
     * @return \SimpleSAML\SAML2\XML\md\Organization|null
     */
    public function getOrganization()
    {
        return $this->organization;
    }


    /**
     * Collect the value of the ContactPersons property.
     *
     * @return \SimpleSAML\SAML2\XML\md\ContactPerson[]
     */
    public function getContactPersons()
    {
        return $this->contacts;
    }


    /**
     * Collect the value of the KeyDescriptors property.
     *
     * @return \SimpleSAML\SAML2\XML\md\KeyDescriptor[]
     */
    public function getKeyDescriptors()
    {
        return $this->keyDescriptors;
    }


    /**
     * Add this RoleDescriptor to an EntityDescriptor.
     *
     * @param \DOMElement $parent The EntityDescriptor we should append this endpoint to.
     * @return \DOMElement
     */
    public function toUnsignedXML(?DOMElement $parent = null): DOMElement
    {
        $e = parent::toUnsignedXML($parent);

        $e->setAttribute('protocolSupportEnumeration', implode(' ', $this->getProtocolSupportEnumeration()));

        if ($this->getErrorURL() !== null) {
            $e->setAttribute('errorURL', $this->getErrorURL());
        }

        foreach ($this->getKeyDescriptors() as $kd) {
            $kd->toXML($e);
        }

        $this->getOrganization()?->toXML($e);

        foreach ($this->getContactPersons() as $cp) {
            $cp->toXML($e);
        }

        return $e;
    }
}
