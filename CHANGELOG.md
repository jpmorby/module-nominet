# Changelog

## [2.0.2] - 2026-08-21

### Added
- Nominet EPP extensions: contact-nom-ext and std-notifications namespaces
- EPP poll queue processing cron task with Nominet notification parsing, gated by a per-account `poll_enabled` feature switch (defaults to off)
- Domain delete, contact delete, and host update EPP operations
- EPP connection caching and proper session logout
- Contact validation for Nominet registrant fields
- DNSSEC tabs now shown unconditionally, with algorithm/digest type dropdowns prefixed by their numeric identifiers
- `(Testbed)` indicator next to the username in the module rows list and group account listboxes, backed by a computed `display_name` meta field used as `row_key`
- `cost_price` per-account meta field, with `getTldPricing()` / `getFilteredTldPricing()` implementations for domain pricing
- Support for .uk domain transfers via IPS tag re-tagging: `checkTransferAvailability()` allows checkout to proceed, `addService()` skips registration for transfers, and `transferDomain()` lets the service be created while the customer is emailed re-tag instructions (`{module.username}` now exposed as an email tag)

### Fixed
- Phone number formatting: strip trunk prefix and prevent double `+` prefix
- DNSSEC tab showing stale data after add/delete (records now fetched after processing, not before)
- EPP login password now wrapped in CDATA to handle XML special characters
- Renamed the `sandbox` module row meta key and terminology to `testbed` to match Nominet's terminology, with an upgrade migration for existing rows

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
