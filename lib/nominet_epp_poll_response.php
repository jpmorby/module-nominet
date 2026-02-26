<?php

use Metaregistrar\EPP\eppPollResponse;

/**
 * Nominet EPP Poll Response
 *
 * Parses Nominet-specific poll notification messages using the
 * std-notifications-1.0 and std-notifications-1.1 namespaces.
 *
 * @see https://registrars.nominet.uk/uk-namespace/registration-and-domain-management/registration-systems/epp/epp-notifications/
 */
class NominetEppPollResponse extends eppPollResponse
{
    // Nominet notification types (std-notifications-1.0)
    const TYPE_DOMAIN_CANCELLED = 'cancData';
    const TYPE_DOMAINS_RELEASED = 'relData';
    const TYPE_REGISTRAR_CHANGE = 'rcData';
    const TYPE_REFERRAL_REJECTED = 'domainFailData';
    const TYPE_REGISTRANT_TRANSFER = 'trnData';

    // Nominet notification types (std-notifications-1.1)
    const TYPE_DATA_QUALITY = 'processData';
    const TYPE_DOMAINS_SUSPENDED = 'suspData';

    // Standard EPP types handled by parent
    const TYPE_REFERRAL_ACCEPTED = 'creData';
    const TYPE_ACCOUNT_CHANGE = 'contactInfData';

    private $nominetMessageType = null;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Determine the Nominet notification type from the poll response
     *
     * @return string|null One of the TYPE_* constants, or null if not a Nominet notification
     */
    public function getNominetMessageType()
    {
        if ($this->nominetMessageType !== null) {
            return $this->nominetMessageType;
        }

        $xpath = $this->xPath();

        // Check std-notifications-1.0 types
        $types_v10 = [
            self::TYPE_DOMAIN_CANCELLED,
            self::TYPE_DOMAINS_RELEASED,
            self::TYPE_REGISTRAR_CHANGE,
            self::TYPE_REFERRAL_REJECTED,
            self::TYPE_REGISTRANT_TRANSFER,
        ];
        foreach ($types_v10 as $type) {
            $result = $xpath->query('/epp:epp/epp:response/epp:resData/n:' . $type);
            if (is_object($result) && $result->length > 0) {
                $this->nominetMessageType = $type;
                return $type;
            }
        }

        // Check std-notifications-1.1 types
        $types_v11 = [
            self::TYPE_DATA_QUALITY,
            self::TYPE_DOMAINS_SUSPENDED,
        ];
        foreach ($types_v11 as $type) {
            $result = $xpath->query('/epp:epp/epp:response/epp:resData/n11:' . $type);
            if (is_object($result) && $result->length > 0) {
                $this->nominetMessageType = $type;
                return $type;
            }
        }

        // Check standard EPP types
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/domain:creData');
        if (is_object($result) && $result->length > 0) {
            $this->nominetMessageType = self::TYPE_REFERRAL_ACCEPTED;
            return self::TYPE_REFERRAL_ACCEPTED;
        }

        $result = $xpath->query('/epp:epp/epp:response/epp:resData/contact:infData');
        if (is_object($result) && $result->length > 0) {
            $this->nominetMessageType = self::TYPE_ACCOUNT_CHANGE;
            return self::TYPE_ACCOUNT_CHANGE;
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Domain Cancelled (cancData)
    // -------------------------------------------------------------------------

    /**
     * Get the cancelled domain name
     *
     * @return string|null
     */
    public function getCancelledDomainName()
    {
        return $this->queryPath('/epp:epp/epp:response/epp:resData/n:cancData/n:domainName');
    }

    /**
     * Get the originator of the cancellation
     *
     * @return string|null
     */
    public function getCancellationOriginator()
    {
        return $this->queryPath('/epp:epp/epp:response/epp:resData/n:cancData/n:orig');
    }

    // -------------------------------------------------------------------------
    // Domains Released (relData)
    // -------------------------------------------------------------------------

    /**
     * Get the account ID for released domains
     *
     * @return string|null
     */
    public function getReleasedAccountId()
    {
        return $this->queryPath('/epp:epp/epp:response/epp:resData/n:relData/n:accountId');
    }

    /**
     * Check if the account was moved
     *
     * @return string|null "Y" if moved
     */
    public function getReleasedAccountMoved()
    {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/n:relData/n:accountId/@moved');
        if (is_object($result) && $result->length > 0) {
            return $result->item(0)->nodeValue;
        }
        return null;
    }

    /**
     * Get the originating registrar tag
     *
     * @return string|null
     */
    public function getReleasedFromTag()
    {
        return $this->queryPath('/epp:epp/epp:response/epp:resData/n:relData/n:from');
    }

    /**
     * Get the destination registrar tag
     *
     * @return string|null
     */
    public function getReleasedToTag()
    {
        return $this->queryPath('/epp:epp/epp:response/epp:resData/n:relData/n:registrarTag');
    }

    /**
     * Get the list of released domain names
     *
     * @return array
     */
    public function getReleasedDomainNames()
    {
        return $this->getDomainList('/epp:epp/epp:response/epp:resData/n:relData/n:domainListData/n:domainName');
    }

    // -------------------------------------------------------------------------
    // Registrar Change (rcData)
    // -------------------------------------------------------------------------

    /**
     * Get the originator of the registrar change
     *
     * @return string|null
     */
    public function getRegistrarChangeOriginator()
    {
        return $this->queryPath('/epp:epp/epp:response/epp:resData/n:rcData/n:orig');
    }

    /**
     * Get the registrar tag for the change
     *
     * @return string|null
     */
    public function getRegistrarChangeTag()
    {
        return $this->queryPath('/epp:epp/epp:response/epp:resData/n:rcData/n:registrarTag');
    }

    /**
     * Get the case ID for the registrar change authorization request
     *
     * @return string|null
     */
    public function getRegistrarChangeCaseId()
    {
        return $this->queryPath('/epp:epp/epp:response/epp:resData/n:rcData/n:caseId');
    }

    /**
     * Get domain names from a registrar change notification
     *
     * @return array
     */
    public function getRegistrarChangeDomainNames()
    {
        $xpath = $this->xPath();
        $domains = [];

        $result = $xpath->query('/epp:epp/epp:response/epp:resData/n:rcData/n:domainListData/domain:infData/domain:name');
        if (is_object($result) && $result->length > 0) {
            foreach ($result as $node) {
                $domains[] = $node->nodeValue;
            }
        }

        return $domains;
    }

    // -------------------------------------------------------------------------
    // Referral Rejected (domainFailData)
    // -------------------------------------------------------------------------

    /**
     * Get the domain name that was rejected
     *
     * @return string|null
     */
    public function getRejectedDomainName()
    {
        return $this->queryPath('/epp:epp/epp:response/epp:resData/n:domainFailData/n:domainName');
    }

    /**
     * Get the rejection reason
     *
     * @return string|null
     */
    public function getRejectionReason()
    {
        return $this->queryPath('/epp:epp/epp:response/epp:resData/n:domainFailData/n:reason');
    }

    // -------------------------------------------------------------------------
    // Registrant Transfer (trnData)
    // -------------------------------------------------------------------------

    /**
     * Get the originator of the registrant transfer
     *
     * @return string|null
     */
    public function getRegistrantTransferOriginator()
    {
        return $this->queryPath('/epp:epp/epp:response/epp:resData/n:trnData/n:orig');
    }

    /**
     * Get the new account ID after registrant transfer
     *
     * @return string|null
     */
    public function getRegistrantTransferAccountId()
    {
        return $this->queryPath('/epp:epp/epp:response/epp:resData/n:trnData/n:accountId');
    }

    /**
     * Get the old account ID before registrant transfer
     *
     * @return string|null
     */
    public function getRegistrantTransferOldAccountId()
    {
        return $this->queryPath('/epp:epp/epp:response/epp:resData/n:trnData/n:oldAccountId');
    }

    /**
     * Get the domain names affected by registrant transfer
     *
     * @return array
     */
    public function getRegistrantTransferDomainNames()
    {
        return $this->getDomainList('/epp:epp/epp:response/epp:resData/n:trnData/n:domainListData/n:domainName');
    }

    // -------------------------------------------------------------------------
    // Data Quality (processData) - std-notifications-1.1
    // -------------------------------------------------------------------------

    /**
     * Get the data quality process stage
     *
     * @return string|null e.g. "initial", "updated"
     */
    public function getDataQualityStage()
    {
        $xpath = $this->xPath();
        $result = $xpath->query('/epp:epp/epp:response/epp:resData/n11:processData/@stage');
        if (is_object($result) && $result->length > 0) {
            return $result->item(0)->nodeValue;
        }
        return null;
    }

    /**
     * Get the data quality process type
     *
     * @return string|null
     */
    public function getDataQualityProcessType()
    {
        return $this->queryPath('/epp:epp/epp:response/epp:resData/n11:processData/n11:processType');
    }

    /**
     * Get the suspension date from data quality notification
     *
     * @return string|null
     */
    public function getDataQualitySuspendDate()
    {
        return $this->queryPath('/epp:epp/epp:response/epp:resData/n11:processData/n11:suspendDate');
    }

    /**
     * Get the domain names affected by data quality process
     *
     * @return array
     */
    public function getDataQualityDomainNames()
    {
        return $this->getDomainList('/epp:epp/epp:response/epp:resData/n11:processData/n11:domainListData/n11:domainName');
    }

    // -------------------------------------------------------------------------
    // Domains Suspended (suspData) - std-notifications-1.1
    // -------------------------------------------------------------------------

    /**
     * Get the suspension reason
     *
     * @return string|null
     */
    public function getSuspensionReason()
    {
        return $this->queryPath('/epp:epp/epp:response/epp:resData/n11:suspData/n11:reason');
    }

    /**
     * Get the cancellation date for suspended domains
     *
     * @return string|null
     */
    public function getSuspensionCancelDate()
    {
        return $this->queryPath('/epp:epp/epp:response/epp:resData/n11:suspData/n11:cancelDate');
    }

    /**
     * Get the domain names that were suspended
     *
     * @return array
     */
    public function getSuspendedDomainNames()
    {
        return $this->getDomainList('/epp:epp/epp:response/epp:resData/n11:suspData/n11:domainListData/n11:domainName');
    }

    // -------------------------------------------------------------------------
    // Referral Accepted (standard domain:creData)
    // -------------------------------------------------------------------------

    /**
     * Get the domain name from a referral accepted notification
     *
     * @return string|null
     */
    public function getAcceptedDomainName()
    {
        return $this->queryPath('/epp:epp/epp:response/epp:resData/domain:creData/domain:name');
    }

    /**
     * Get the creation date from a referral accepted notification
     *
     * @return string|null
     */
    public function getAcceptedCreationDate()
    {
        return $this->queryPath('/epp:epp/epp:response/epp:resData/domain:creData/domain:crDate');
    }

    /**
     * Get the expiration date from a referral accepted notification
     *
     * @return string|null
     */
    public function getAcceptedExpirationDate()
    {
        return $this->queryPath('/epp:epp/epp:response/epp:resData/domain:creData/domain:exDate');
    }

    // -------------------------------------------------------------------------
    // Helper methods
    // -------------------------------------------------------------------------

    /**
     * Extract a list of domain names from an XPath query
     *
     * @param string $path The XPath expression
     * @return array
     */
    private function getDomainList($path)
    {
        $xpath = $this->xPath();
        $domains = [];

        $result = $xpath->query($path);
        if (is_object($result) && $result->length > 0) {
            foreach ($result as $node) {
                $domains[] = $node->nodeValue;
            }
        }

        return $domains;
    }
}
