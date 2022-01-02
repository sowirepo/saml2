<?php

declare(strict_types=1);

namespace SimpleSAML\SAML2\XML\saml;

use SimpleSAML\SAML2\Utils;
use SimpleSAML\XML\AbstractXMLElement;
use SimpleSAML\XML\DOMDocumentFactory;
use SimpleSAML\XML\XMLElementInterface;
use SimpleSAML\XMLSecurity\Alg\Encryption\EncryptionAlgorithmInterface;
use SimpleSAML\XMLSecurity\Backend\EncryptionBackend;
use SimpleSAML\XMLSecurity\Utils\Security;
use SimpleSAML\XMLSecurity\XML\EncryptedElementInterface;
use SimpleSAML\XMLSecurity\XML\EncryptedElementTrait;
use SimpleSAML\XMLSecurity\XMLSecurityKey;

/**
 * Class handling encrypted assertions.
 *
 * @package simplesamlphp/saml2
 */
class EncryptedAssertion extends AbstractSamlElement implements EncryptedElementInterface
{
    use EncryptedElementTrait;

    /** @var bool */
    protected bool $wasSignedAtConstruction = false;


    /**
     * @return \SimpleSAML\XMLSecurity\Backend\EncryptionBackend|null The encryption backend to use, or null if we want
     * to use the default.
     */
    public function getEncryptionBackend(): ?EncryptionBackend
    {
//        return $this->backend;
    }


    /**
     * @return string[]|null An array with all algorithm identifiers that we want to blacklist, or null if we want to
     * use the defaults.
     */
    public function getBlacklistedAlgorithms(): ?array
    {
//        return $this->blacklistedAlgs;
    }


    /**
     * @inheritDoc
     */
    public function decrypt(EncryptionAlgorithmInterface $decryptor): XMLElementInterface
    {
        return Assertion::fromXML(
            DOMDocumentFactory::fromString($this->decryptData($decryptor))->documentElement
        );
    }

    /**
     * @return bool
     */
    public function wasSignedAtConstruction(): bool
    {
        return $this->wasSignedAtConstruction;
    }
}
