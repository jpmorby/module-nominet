<?php

use Metaregistrar\EPP\eppConnection;

/**
 * Nominet EPP Connection
 *
 * @package blesta
 * @subpackage blesta.components.modules.nominet
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class NominetEppConnection extends eppConnection
{
    public function __construct($logging = false, $settingsfile = null)
    {
        parent::__construct($logging, $settingsfile);

        $this->enableDnssec();

        // Nominet extensions (declared in login)
        $this->addExtension('std-notifications', 'http://www.nominet.org.uk/epp/xml/std-notifications-1.2');
        $this->addExtension('contact-nom-ext', 'http://www.nominet.org.uk/epp/xml/contact-nom-ext-1.0');

        // Nominet notification data uses 1.0/1.1 namespace URIs in actual XML,
        // register these for XPath resolution in poll responses
        $this->xpathuri['http://www.nominet.org.uk/epp/xml/std-notifications-1.0'] = 'n';
        $this->xpathuri['http://www.nominet.org.uk/epp/xml/std-notifications-1.1'] = 'n11';

        // Map Nominet request classes to response classes
        $this->addCommandResponse('NominetEppCreateContactRequest', 'Metaregistrar\\EPP\\eppCreateContactResponse');
        $this->addCommandResponse('NominetEppUpdateContactRequest', 'Metaregistrar\\EPP\\eppUpdateResponse');
        $this->addCommandResponse('Metaregistrar\\EPP\\eppPollRequest', 'NominetEppPollResponse');
    }
}
