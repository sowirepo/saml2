<?php

declare(strict_types=1);

namespace SimpleSAML\SAML2\XML\saml;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
//use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\ExtendableElementTrait;

use function array_filter;
use function array_values;

/**
 * Class representing SAML2 Evidence
 *
 * @package simplesamlphp/saml2
 */
final class Evidence extends AbstractSamlElement
{
    use ExtendableElementTrait;

    /**
     * Initialize an Evidence.
     *
     * @param (\SimpleSAML\SAML2\XML\saml\AssertionIdRef|
     *         \SimpleSAML\SAML2\XML\saml\AssertionURIRef|
     *         \SimpleSAML\SAML2\XML\saml\Assertion|
     *         \SimpleSAML\SAML2\XML\saml\EncryptedAssertion)[] $elements
     */
    public function __construct(array $elements) {
        $this->setElements($elements);
    }


    /**
     * Collect the AssertionIdRef objects
     *
     * @return \SimpleSAML\SAML2\XML\saml\AssertionIdRef[]
     */
    public function getAssertionIdRefs(): array
    {
        return array_values(array_filter($this->elements, function ($elt) {
            return $elt instanceof AssertionIdRef;
        }));
    }


    /**
     * Collect the AssertionURIRef objects
     *
     * @return \SimpleSAML\SAML2\XML\saml\AssertionURIRef[]
     */
    public function getAssertionURIRefs(): array
    {
        return array_values(array_filter($this->elements, function ($elt) {
            return $elt instanceof AssertionURIRef;
        }));
    }


    /**
     * Collect the Assertion objects
     *
     * @return \SimpleSAML\SAML2\XML\saml\Assertion[]
     */
    public function getAssertions(): array
    {
        return array_values(array_filter($this->elements, function ($elt) {
            return $elt instanceof Assertion;
        }));
    }


    /**
     * Collect the Assertion objects
     *
     * @return \SimpleSAML\SAML2\XML\saml\EncryptedAssertion[]
     */
    public function getEncryptedAssertions(): array
    {
        return array_values(array_filter($this->elements, function ($elt) {
            return $elt instanceof EncryptedAssertion;
        }));
    }


    /**
     * Convert XML into a Evidence
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException if the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'Evidence', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, Evidence::NS, InvalidDOMElementException::class);

        $assertionIdRefs = AssertionIdRef::getChildrenOfClass($xml);
        $assertionURIRefs = AssertionURIRef::getChildrenOfClass($xml);
        $assertions = Assertion::getChildrenOfClass($xml);
        $encryptedAssertions = EncryptedAssertion::getChildrenOfClass($xml);

        return new self(array_merge(
            $assertionIdRefs,
            $assertionURIRefs,
            $assertions,
            $encryptedAssertions
        ));
    }


    /**
     * Convert this Evidence to XML.
     *
     * @param \DOMElement|null $parent The element we should append this Evidence to.
     * @return \DOMElement
     */
    public function toXML(DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        foreach ($this->getElements() as $elt) {
            $elt->toXML($e);
        }

        return $e;
    }
}
