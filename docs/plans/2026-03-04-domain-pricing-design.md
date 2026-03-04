# Domain Pricing Feature Design

**Date:** 2026-03-04
**Status:** Approved

## Overview

Implement `getTldPricing` / `getFilteredTldPricing` for the Nominet module so that
Blesta's domain pricing sync can populate all TLD prices (1–10 years, all currencies)
from a single cost price configured per module row.

## Background

Nominet charges a single annual registry fee that applies uniformly to all TLDs
(`.uk`, `.co.uk`, `.org.uk`, etc.). The price for N years is simply `base_price × N`.
Blesta's domain pricing feature calls `getFilteredTldPricing()` expecting:

```
[tld => [currency => [year => ['register' => price, 'transfer' => price, 'renew' => price]]]]
```

It then applies configurable markup and stores the result as package pricing.

## Data Storage

A `cost_price` meta field is added to the module row, stored alongside existing
fields (username, password, testbed, poll_enabled). It holds a plain decimal string
representing the per-year cost in GBP (e.g. `"7.50"`).

No migration is needed for existing rows — `cost_price` defaults to `0` if absent,
and `getFilteredTldPricing()` returns `[]` in that case.

## Pricing Logic

- **Base currency:** GBP (Nominet's billing currency)
- **Currency conversion:** Blesta's `Currencies::convert()` model converts GBP to
  each company-configured currency
- **Years:** 1–10, price = `converted_price × years`
- **Operations:** register = transfer = renew = same cost price (Nominet IPS tag
  transfers carry no registry fee; Blesta applies its own per-operation markup)
- **Filters:** `tlds`, `currencies`, `terms` filter arrays are respected
- **No result:** returns `[]` when cost_price is unset or zero

## Files Changed

| File | Change |
|------|--------|
| `nominet.php` | Add `cost_price` to `$meta_fields`, `getRowRules()`, and implement `getTldPricing()` / `getFilteredTldPricing()` |
| `views/default/add_row.pdt` | Add cost price text input |
| `views/default/edit_row.pdt` | Add cost price text input |
| `language/en_us/nominet.php` | Add label and validation error strings |
| `config.json` | Bump version to 1.2.2 |

## Version

Bump to **1.2.2** — new feature, no breaking changes, no data migration required.
