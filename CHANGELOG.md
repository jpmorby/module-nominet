# Changelog

## [1.2.0] - 2026-02-26

### Added
- EPP poll queue processing cron task with Nominet notification parsing
- Poll feature switch (`poll_enabled`) per account, defaults to off
- Nominet EPP extensions: contact-nom-ext and std-notifications namespaces
- Custom poll response class for parsing Nominet notification types (domain cancelled, released, registrar change, referral rejected/accepted, registrant transfer, data quality, domains suspended)
- EPP connection caching to reuse sessions within a single request
- Domain delete, contact delete, host update, and poll EPP operations
- Contact validation for Nominet registrant fields (type, trading name, company number)
- DNSSEC management (add/remove DS records)
- IPS tag push (domain transfer to another registrar)

### Fixed
- Phone number formatting: strip trunk prefix and prevent double `+` prefix
- Contact updates now use `eppUpdateContactRequest` instead of creating new contacts
- EPP connection properly logs out on session teardown
- Whois tab labels corrected for Nominet registrant model
- Multiple bug fixes: wrong filename, broken contact updates, premature domain update

## [1.1.0] - 2024-05-03

### Added
- Additional TLD support

### Fixed
- Phone number sanitisation (duplicate `+` signs, non-numeric characters)
- Domain contact add/get methods
- Various registration and whois tab errors

## [1.0.0] - 2023-12-21

### Added
- Initial release with Nominet EPP integration
- Domain registration, renewal, and transfer
- Nameserver management
- Contact (registrant) management
- Admin and client service tabs
