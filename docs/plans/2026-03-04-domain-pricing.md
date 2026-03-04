# Domain Pricing Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a `cost_price` field to the module row and implement `getFilteredTldPricing()` so Blesta can sync domain prices for all Nominet TLDs across 1–10 years in all configured currencies.

**Architecture:** `cost_price` (GBP decimal) is stored as module row meta. `getFilteredTldPricing()` reads it, uses Blesta's `Currencies::convert()` to produce prices in all currencies, and returns the standard pricing array for all 10 Nominet TLDs × 10 years × 3 operations.

**Tech Stack:** PHP 7.4+, Blesta RegistrarModule, Blesta Currencies model (`$this->Currencies->convert()`), `Configure::get('Nominet.tlds')` for TLD list.

---

### Task 1: Add language strings

**Files:**
- Modify: `language/en_us/nominet.php`

**Step 1: Add two strings after the `poll_enabled` line**

In `language/en_us/nominet.php`, after:
```php
$lang['Nominet.!error.poll_enabled.format'] = 'Poll enabled must be "true" or "false".';
```
Add:
```php
$lang['Nominet.row_meta.cost_price'] = 'Cost Price (GBP)';
$lang['Nominet.!error.cost_price.format'] = 'Cost price must be a valid non-negative number.';
```

**Step 2: Verify syntax**
```bash
php -l language/en_us/nominet.php
```
Expected: `No syntax errors detected`

**Step 3: Commit**
```bash
git add language/en_us/nominet.php
git commit --author="Jon Morby <jon@fxrm.com>" -m "Add cost_price language strings"
```

---

### Task 2: Add cost_price to module row meta

**Files:**
- Modify: `nominet.php` — `addModuleRow()`, `editModuleRow()`, `getRowRules()`

**Step 1: Add `cost_price` to `$meta_fields` in `addModuleRow()`**

Find this line in `addModuleRow()`:
```php
$meta_fields = ['username', 'password', 'secure', 'testbed', 'poll_enabled'];
```
Change to:
```php
$meta_fields = ['username', 'password', 'secure', 'testbed', 'poll_enabled', 'cost_price'];
```

**Step 2: Add `cost_price` to `$meta_fields` in `editModuleRow()`**

Same change in `editModuleRow()`:
```php
$meta_fields = ['username', 'password', 'secure', 'testbed', 'poll_enabled', 'cost_price'];
```

**Step 3: Add validation rule to `getRowRules()`**

In `getRowRules()`, after the `poll_enabled` rule block (before the closing `]` of `$rules`):
```php
'cost_price' => [
    'format' => [
        'rule' => ['matches', '/^\d+(\.\d{1,4})?$/'],
        'message' => Language::_('Nominet.!error.cost_price.format', true)
    ]
],
```

**Step 4: Verify syntax**
```bash
php -l nominet.php
```
Expected: `No syntax errors detected`

**Step 5: Commit**
```bash
git add nominet.php
git commit --author="Jon Morby <jon@fxrm.com>" -m "Add cost_price meta field to module row"
```

---

### Task 3: Add cost_price input to add/edit row views

**Files:**
- Modify: `views/default/add_row.pdt`
- Modify: `views/default/edit_row.pdt`

**Step 1: Add field to `add_row.pdt`**

After the `poll_enabled` `<li>` block in `add_row.pdt`, add:
```php
                    <li>
                        <?php
                        $this->Form->label($this->_('Nominet.row_meta.cost_price', true), 'cost_price');
                        $this->Form->fieldText('cost_price', ($vars->cost_price ?? null), ['id' => 'cost_price', 'class' => 'block']);
                        ?>
                    </li>
```

**Step 2: Add same field to `edit_row.pdt`**

Same block, after the `poll_enabled` `<li>` in `edit_row.pdt`.

**Step 3: Commit**
```bash
git add views/default/add_row.pdt views/default/edit_row.pdt
git commit --author="Jon Morby <jon@fxrm.com>" -m "Add cost_price input to add/edit row views"
```

---

### Task 4: Implement getTldPricing and getFilteredTldPricing

**Files:**
- Modify: `nominet.php` — add two new public methods

**Step 1: Add both methods**

Find the end of `nominet.php` — locate the last method before the closing `}` of the class. Add these two methods just before the closing brace:

```php
    /**
     * Get a list of the TLD prices
     *
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return array A list of all TLDs and their pricing
     *    [tld => [currency => [year# => ['register' => price, 'transfer' => price, 'renew' => price]]]]
     */
    public function getTldPricing($module_row_id = null)
    {
        return $this->getFilteredTldPricing($module_row_id);
    }

    /**
     * Get a filtered list of the TLD prices
     *
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @param array $filters A list of criteria by which to filter fetched pricings including:
     *  - tlds A list of tlds for which to fetch pricings
     *  - currencies A list of currencies for which to fetch pricings
     *  - terms A list of terms for which to fetch pricings
     * @return array A list of all TLDs and their pricing
     *    [tld => [currency => [year# => ['register' => price, 'transfer' => price, 'renew' => price]]]]
     */
    public function getFilteredTldPricing($module_row_id = null, $filters = [])
    {
        // Get cost_price from the specified row, or the first available row
        if ($module_row_id !== null) {
            $row = $this->getModuleRow($module_row_id);
        } else {
            $rows = $this->getModuleRows();
            $row = $rows[0] ?? null;
        }

        $cost_price = (float) ($row->meta->cost_price ?? 0);

        if ($cost_price <= 0) {
            return [];
        }

        Loader::loadModels($this, ['Currencies']);

        $tlds = Configure::get('Nominet.tlds');
        $currencies = $this->Currencies->getAll(Configure::get('Blesta.company_id'));
        $pricing = [];

        foreach ($tlds as $tld) {
            if (isset($filters['tlds']) && !in_array($tld, $filters['tlds'])) {
                continue;
            }

            $pricing[$tld] = [];

            foreach ($currencies as $currency) {
                if (isset($filters['currencies']) && !in_array($currency->code, $filters['currencies'])) {
                    continue;
                }

                $converted = $this->Currencies->convert(
                    $cost_price,
                    'GBP',
                    $currency->code,
                    Configure::get('Blesta.company_id')
                );

                $pricing[$tld][$currency->code] = [];

                foreach (range(1, 10) as $years) {
                    if (isset($filters['terms']) && !in_array($years, $filters['terms'])) {
                        continue;
                    }

                    $pricing[$tld][$currency->code][$years] = [
                        'register' => $converted * $years,
                        'transfer' => $converted * $years,
                        'renew'    => $converted * $years,
                    ];
                }
            }
        }

        return $pricing;
    }
```

**Step 2: Verify syntax**
```bash
php -l nominet.php
```
Expected: `No syntax errors detected`

**Step 3: Commit**
```bash
git add nominet.php
git commit --author="Jon Morby <jon@fxrm.com>" -m "Implement getTldPricing and getFilteredTldPricing"
```

---

### Task 5: Bump version to 1.2.2

**Files:**
- Modify: `config.json`

**Step 1: Update version**

In `config.json`, change:
```json
"version": "1.2.1",
```
to:
```json
"version": "1.2.2",
```

**Step 2: Commit**
```bash
git add config.json
git commit --author="Jon Morby <jon@fxrm.com>" -m "Bump version to 1.2.2"
```

---

### Task 6: Push and verify

**Step 1: Push**
```bash
git push
```

**Step 2: Manual verification in Blesta**

1. In Blesta admin, go to **Settings > Modules > Nominet > Edit** on a module row
2. Confirm the **Cost Price (GBP)** field appears below Poll Enabled
3. Enter a price (e.g. `7.50`) and save — confirm no validation errors
4. Go to **Domains > TLDs**, select a `.co.uk` TLD and run **Sync Prices**
5. Confirm prices populate for all configured currencies at 1–10 year terms
6. Check that the 2-year price is exactly double the 1-year price

**Step 3: Edge case — zero price**
- Leave cost_price blank on a row, confirm that `getFilteredTldPricing()` returns `[]` and Blesta doesn't error (it skips modules that return empty)
