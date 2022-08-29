<?php

declare(strict_types=1);

namespace SimpleSAML\Test\SAML2\XML\md;

use DOMAttr;
use DOMDocument;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Assert\AssertionFailedException;
use SimpleSAML\SAML2\Compat\ContainerSingleton;
use SimpleSAML\SAML2\Compat\MockContainer;
use SimpleSAML\SAML2\Exception\ProtocolViolationException;
use SimpleSAML\SAML2\XML\md\Company;
use SimpleSAML\SAML2\XML\md\ContactPerson;
use SimpleSAML\SAML2\XML\md\EmailAddress;
use SimpleSAML\SAML2\XML\md\EncryptionMethod;
use SimpleSAML\SAML2\XML\md\Extensions;
use SimpleSAML\SAML2\XML\md\GivenName;
use SimpleSAML\SAML2\XML\md\KeyDescriptor;
use SimpleSAML\SAML2\XML\md\Organization;
use SimpleSAML\SAML2\XML\md\OrganizationDisplayName;
use SimpleSAML\SAML2\XML\md\OrganizationName;
use SimpleSAML\SAML2\XML\md\OrganizationUrl;
use SimpleSAML\SAML2\XML\md\SurName;
use SimpleSAML\SAML2\XML\md\TelephoneNumber;
use SimpleSAML\SAML2\XML\md\UnknownRoleDescriptor;
use SimpleSAML\SAML2\XML\saml\Audience;
use SimpleSAML\Test\SAML2\Constants as C;
use SimpleSAML\Test\SAML2\CustomRoleDescriptor;
use SimpleSAML\Test\XML\SchemaValidationTestTrait;
use SimpleSAML\Test\XML\SerializableXMLTestTrait;
use SimpleSAML\XML\DOMDocumentFactory;
use SimpleSAML\XML\Exception\MissingAttributeException;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\Chunk;
use SimpleSAML\XMLSecurity\XML\ds\KeyInfo;
use SimpleSAML\XMLSecurity\XML\ds\KeyName;

use function dirname;
use function strval;

/**
 * This is a test for the UnknownRoleDescriptor class.
 *
 * @covers \SimpleSAML\SAML2\XML\md\UnknownRoleDescriptor
 * @covers \SimpleSAML\SAML2\XML\md\AbstractRoleDescriptor
 * @covers \SimpleSAML\SAML2\XML\md\AbstractRoleDescriptorType
 * @covers \SimpleSAML\SAML2\XML\md\AbstractMetadataDocument
 * @covers \SimpleSAML\SAML2\XML\md\AbstractMdElement
 *
 * @package simplesamlphp/saml2
 */
final class UnknownRoleDescriptorTest extends TestCase
{
//    use SchemaValidationTestTrait;
    use SerializableXMLTestTrait;


    /**
     */
    public function setUp(): void
    {
//        $this->schema = dirname(dirname(dirname(dirname(__FILE__)))) . '/resources/schemas/simplesamlphp.xsd';

        $this->testedClass = AbstractRoleDescriptor::class;

        $this->xmlRepresentation = DOMDocumentFactory::fromFile(
            dirname(dirname(dirname(dirname(__FILE__)))) . '/resources/xml/md_RoleDescriptor.xml'
        );

        $container = new MockContainer();
        $container->registerExtensionHandler(CustomRoleDescriptor::class);
        ContainerSingleton::setContainer($container);
    }


    // marshalling


    /**
     */
    public function testMarshalling(): void
    {
        $doc = DOMDocumentFactory::fromString('<root/>');
        $attr_cp_1 = $doc->createAttributeNS('urn:test:something', 'test:attr1');
        $attr_cp_1->value = 'testval1';
        $attr_cp_2 = $doc->createAttributeNS('urn:test:something', 'test:attr2');
        $attr_cp_2->value = 'testval2';

        $roleDescriptor = new CustomRoleDescriptor(
            [new Audience('urn:some:audience')],
            [C::NS_SAMLP, C::PROTOCOL],
            'TheID',
            1234567890,
            'PT5000S',
            new Extensions([new Chunk(DOMDocumentFactory::fromString('<ssp:Chunk xmlns:ssp="urn:x-simplesamlphp:namespace">Some</ssp:Chunk>')->documentElement)]),
            'https://error.reporting/',
            [
                new KeyDescriptor(
                    new KeyInfo([new KeyName('IdentityProvider.com SSO Signing Key')]),
                    'signing',
                ),
                new KeyDescriptor(
                    new KeyInfo([new KeyName('IdentityProvider.com SSO Encryption Key')]),
                    'encryption',
                    [new EncryptionMethod(C::KEY_TRANSPORT_OAEP_MGF1P)],
                )
            ],
            new Organization(
               [new OrganizationName('en', 'Identity Providers R US')],
               [new OrganizationDisplayName('en', 'Identity Providers R US, a Division of Lerxst Corp.')],
               [new OrganizationURL('en', 'https://IdentityProvider.com')],
            ),
            [
                new ContactPerson(
                    'other',
                    new Company('Test Company'),
                    new GivenName('John'),
                    new SurName('Doe'),
                    null,
                    [new EmailAddress('mailto:jdoe@test.company'), new EmailAddress('mailto:john.doe@test.company')],
                    [new TelephoneNumber('1-234-567-8901')],
                    [$attr_cp_1, $attr_cp_2],
                ),
                new ContactPerson('technical', null, null, null, null, [], [new TelephoneNumber('1-234-567-8901')]),
            ],
            [new DOMAttr('phpunit', 'test')],
        );

        $this->assertEquals(
            $this->xmlRepresentation->saveXML($this->xmlRepresentation->documentElement),
            strval($roleDescriptor)
        );
    }


    // test unmarshalling


    /**
     * Test unmarshalling an unknown object as a RoleDescriptor.
    public function testUnmarshalling(): void
    {
        $descriptor = UnknownRoleDescriptor::fromXML($this->xmlRepresentation->documentElement);

        $this->assertCount(2, $descriptor->getKeyDescriptors());
        $this->assertInstanceOf(KeyDescriptor::class, $descriptor->getKeyDescriptors()[0]);
        $this->assertInstanceOf(KeyDescriptor::class, $descriptor->getKeyDescriptors()[1]);
        $this->assertEquals(
            [C::NS_SAMLP, C::PROTOCOL],
            $descriptor->getProtocolSupportEnumeration()
        );
        $this->assertInstanceOf(Organization::class, $descriptor->getOrganization());
        $this->assertCount(2, $descriptor->getContactPersons());
        $this->assertInstanceOf(ContactPerson::class, $descriptor->getContactPersons()[0]);
        $this->assertInstanceOf(ContactPerson::class, $descriptor->getContactPersons()[1]);
        $this->assertEquals('TheID', $descriptor->getID());
        $this->assertEquals(1234567890, $descriptor->getValidUntil());
        $this->assertEquals('PT5000S', $descriptor->getCacheDuration());
        $this->assertEquals('https://error.reporting/', $descriptor->getErrorURL());

        $xml = $descriptor->getXML();
        $this->assertEquals('SomeRoleDescriptor', $xml->localName);

        $extElement = $descriptor->getExtensions();
        $this->assertInstanceOf(Extensions::class, $extElement);

        $extensions = $extElement->getList();
        $this->assertCount(1, $extensions);
        $this->assertInstanceOf(Chunk::class, $extensions[0]);
        $this->assertEquals('urn:x-simplesamlphp:namespace', $extensions[0]->getNamespaceURI());
        $this->assertEquals('Chunk', $extensions[0]->getLocalName());

        $this->assertEquals($this->xmlRepresentation->saveXML(
            $this->xmlRepresentation->documentElement),
            strval($descriptor)
        );
    }
     */


    /**
     * Test creating an UnknownRoleDescriptor from an XML that lacks supported protocols.
    public function testUnmarshallingWithoutSupportedProtocols(): void
    {
        $this->xmlRepresentation->documentElement->removeAttribute('protocolSupportEnumeration');

        $this->expectException(MissingAttributeException::class);
        $this->expectExceptionMessage(
            'Missing \'protocolSupportEnumeration\' attribute on md:UnknownRoleDescriptor.'
        );

        UnknownRoleDescriptor::fromXML($this->xmlRepresentation->documentElement);
    }
     */


    /**
     * Test creating an UnknownRoleDescriptor from an XML that lacks supported protocols.
    public function testUnmarshallingWithEmptySupportedProtocols(): void
    {
        $this->xmlRepresentation->documentElement->setAttribute('protocolSupportEnumeration', '');

        $this->expectException(SchemaViolationException::class);

        UnknownRoleDescriptor::fromXML($this->xmlRepresentation->documentElement);
    }
     */


    /**
     * Test that creating an UnknownRoleDescriptor from XML fails if errorURL is not a valid URL.
    public function testUnmarshallingWithInvalidErrorURL(): void
    {
        $this->xmlRepresentation->documentElement->setAttribute('errorURL', 'not a URL');

        $this->expectException(SchemaViolationException::class);

        UnknownRoleDescriptor::fromXML($this->xmlRepresentation->documentElement);
    }
     */
}
