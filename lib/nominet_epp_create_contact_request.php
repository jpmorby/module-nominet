<?php

use Metaregistrar\EPP\eppCreateContactRequest;
use Metaregistrar\EPP\eppContact;

/**
 * Nominet EPP Create Contact Request with contact-nom-ext extension
 *
 * Adds Nominet-specific extension fields (type, trad-name, co-no) to
 * standard EPP contact:create requests.
 *
 * @see https://registrars.nominet.uk/uk-namespace/registration-and-domain-management/schemas-and-namespaces/
 */
class NominetEppCreateContactRequest extends eppCreateContactRequest
{
    const NAMESPACE_URI = 'http://www.nominet.org.uk/epp/xml/contact-nom-ext-1.0';

    private $registrantType;
    private $tradName;
    private $companyNumber;

    public function __construct(
        eppContact $createinfo,
        $registrantType = null,
        $tradName = null,
        $companyNumber = null
    ) {
        parent::__construct($createinfo);

        $this->registrantType = $registrantType;
        $this->tradName = $tradName;
        $this->companyNumber = $companyNumber;

        if ($registrantType || $tradName || $companyNumber) {
            $this->addNominetExtension();
        }

        $this->addSessionId();
    }

    private function addNominetExtension()
    {
        $this->addExtension('xmlns:contact-ext', self::NAMESPACE_URI);

        $create = $this->createElement('contact-ext:create');

        if ($this->tradName) {
            $create->appendChild($this->createElement('contact-ext:trad-name', $this->tradName));
        }
        if ($this->registrantType) {
            $create->appendChild($this->createElement('contact-ext:type', $this->registrantType));
        }
        if ($this->companyNumber) {
            $create->appendChild($this->createElement('contact-ext:co-no', $this->companyNumber));
        }

        $this->getExtension()->appendChild($create);
    }
}
