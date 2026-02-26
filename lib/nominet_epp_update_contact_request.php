<?php

use Metaregistrar\EPP\eppUpdateContactRequest;
use Metaregistrar\EPP\eppContactHandle;
use Metaregistrar\EPP\eppContact;

/**
 * Nominet EPP Update Contact Request with contact-nom-ext extension
 *
 * Adds Nominet-specific extension fields (type, trad-name, co-no) to
 * standard EPP contact:update requests.
 *
 * @see https://registrars.nominet.uk/uk-namespace/registration-and-domain-management/schemas-and-namespaces/
 */
class NominetEppUpdateContactRequest extends eppUpdateContactRequest
{
    const NAMESPACE_URI = 'http://www.nominet.org.uk/epp/xml/contact-nom-ext-1.0';

    private $registrantType;
    private $tradName;
    private $companyNumber;

    public function __construct(
        eppContactHandle $objectname,
        $addinfo = null,
        $removeinfo = null,
        $updateinfo = null,
        $registrantType = null,
        $tradName = null,
        $companyNumber = null
    ) {
        parent::__construct($objectname, $addinfo, $removeinfo, $updateinfo);

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

        $update = $this->createElement('contact-ext:update');

        if ($this->tradName) {
            $update->appendChild($this->createElement('contact-ext:trad-name', $this->tradName));
        }
        if ($this->registrantType) {
            $update->appendChild($this->createElement('contact-ext:type', $this->registrantType));
        }
        if ($this->companyNumber) {
            $update->appendChild($this->createElement('contact-ext:co-no', $this->companyNumber));
        }

        $this->getExtension()->appendChild($update);
    }
}
