Version: V1.1.0

# Property Tax Collection ISS

The Property Tax Collection Information Support System sub-module maintains information on Property Tax Payment Status data.

## Workflow

Property Tax payment data is imported into tax_payments table via CSV file. The file is stored in the server in storage disk path as defined in config\\filesystems.php for 'importtax'.

Consecutively, triggers are run which creates materialized view with few steps of operations including create status table, and updates proportion column in tables ‘wards’ and ‘grids’.

The table tax_payment_status is created with calculated due_years based on last_payment_date (default value:99) and Match value based on the presence of bin in the system.

## Files

-   Command files: Command files TaxDataImport.php and TaxFunctionBuild.php includes the commands or queries that are supposed to run when the php command is run and are located at app/Console/Commands.
-   Config file: Config file named ‘taxpayment-info.php’ inside the ‘Config’ directory includes all the Sql functions and queries in array form which are called to create commands.
-   Import class file: TaxImport file inside imports directory includes model, rules, map etc. functions and class is called inside store() function of controller class TaxPaymentController

    Location: app/Imports/TaxImport.php

## Commands

For the initial launching, run the following command:

| *php artisan buildfunction:tax* |
|---------------------------------|

The above code creates/builds functions and triggers in the database.

To test if the import command is working or not. Use Command:

| *php artisan import:tax* |
|--------------------------|

## Property Tax Owner PII Security

The Property Tax module protects the owner information stored in `taxpayment_info.tax_payments`. The protected columns are:

-   `owner_name`
-   `owner_contact`

These values are encrypted before they are stored. Property Tax uses the shared `PiiEncryptionService`, active key version and external key provider used by the Building and Application modules. It does not generate or require a separate Property Tax encryption key.

The non-PII fields, including `tax_code` and `last_payment_date`, remain available for normal matching, filtering and reporting. The derived `taxpayment_info.tax_payment_status` data continues to provide Tax Code, BIN, ward and due-year information without requiring owner PII to be revealed.

### PII Data States

| State | Handling |
|-------|----------|
| At rest | `owner_name` and `owner_contact` are stored as versioned AES-256-GCM ciphertext such as `enc:v1:payload`. The encryption key remains outside the project and database. |
| In transit | Authorized plaintext must use HTTPS in production. The PII DataTable and export responses use no-store/no-cache protection. |
| In use | Plaintext exists temporarily in PHP request memory and in the authorized browser or downloaded CSV. The session stores only the user ID and unlock expiry, not owner values or the key. |
| Locked | The DataTable returns the fixed `********` mask for Owner Name. Owner Contact is not selected or returned by the normal DataTable endpoint. |

### Files and Responsibilities

| Component | Location | Responsibility |
|-----------|----------|----------------|
| TaxPayment model | `app\Models\TaxPaymentInfo\TaxPayment.php` | Encrypts `owner_name` and `owner_contact` through model mutators before storage. |
| Tax import | `app\Imports\TaxImport.php` | Maps imported owner values into the TaxPayment model so new CSV imports pass through the encryption mutators. |
| TaxPaymentController | `app\Http\Controllers\TaxPaymentInfo\TaxPaymentController.php` | Supplies reveal status to the view, masks or reveals DataTable Owner Name, audits revealed list access and keeps the normal export free of owner PII. |
| PropertyTaxPiiPresenter | `app\Services\PropertyTaxPiiPresenter.php` | Returns the fixed mask or explicitly decrypted value based on authorized reveal state. |
| PropertyTaxPiiAccessService | `app\Services\PropertyTaxPiiAccessService.php` | Creates, validates, expires and removes the Property Tax-specific privileged session. |
| PropertyTaxPiiAccessController | `app\Http\Controllers\TaxPaymentInfo\PropertyTaxPiiAccessController.php` | Checks permissions and the current login password before granting reveal mode. |
| PropertyTaxPiiExportService | `app\Services\PropertyTaxPiiExportService.php` | Validates Tax Code CSV input and produces an allowlisted PII export. |
| PropertyTaxPiiExportController | `app\Http\Controllers\TaxPaymentInfo\PropertyTaxPiiExportController.php` | Enforces permissions and password re-entry, records audit events and streams the protected CSV response. |
| Backfill command | `app\Console\Commands\EncryptExistingPropertyTaxOwnerPii.php` | Encrypts legacy plaintext owner fields and rebuilds the derived Property Tax status data. |
| Verification command | `app\Console\Commands\VerifyPropertyTaxOwnerPiiEncryption.php` | Confirms that every non-null owner field is encrypted and decryptable without printing its value. |

### Encryption on Import and Write

`TaxPayment::setOwnerNameAttribute()` and `TaxPayment::setOwnerContactAttribute()` pass assigned values to `PiiEncryptionService`. The service:

1. Preserves `null` values.
2. Avoids double encryption when a value already has a valid encrypted prefix.
3. Encrypts plaintext using the configured active key version and AES-256-GCM.
4. Stores versioned ciphertext so future key rotation can identify which key version is required.

`TaxImport::model()` creates a `TaxPayment` model for each imported row. Therefore, newly imported `owner_name` and `owner_contact` values are encrypted automatically through the model mutators. Code that writes directly with `DB::table()->insert()` or raw SQL bypasses these mutators and must encrypt the values explicitly before storage.

After an import, `taxpayment_info.fnc_taxpaymentstatus()` rebuilds the derived Property Tax status information used by the list. Legacy data must be backfilled before plaintext compatibility is disabled.

### Reveal and Lock Flow

The Property Tax list is masked by default. Selecting **View Property Tax Owner PII** opens a password-confirmation form.

`PropertyTaxPiiAccessController::unlock()` requires all of the following:

1. An authenticated user.
2. `List Property Tax Collection` permission.
3. `View Property Tax Owner PII` permission.
4. `Unlock Property Tax Owner PII` permission.
5. Successful verification of the user's current login password.

After successful verification, `PropertyTaxPiiAccessService::unlock()` stores a Property Tax-specific five-minute grant containing the user ID and expiry time. It does not store the password, decrypted owner data or encryption key.

The grant is separate from Building and Application PII grants. Unlocking one module does not unlock another module. The user can lock the Property Tax list manually, and the page submits the lock action automatically when the remaining unlock period expires.

Failure at authentication, module permission, PII permission, password verification, session ownership or expiry blocks plaintext access. A failed password attempt also removes any existing Property Tax PII grant.

### DataTable Handling

`TaxPaymentController::getData()` checks the Property Tax grant on every request.

-   Locked responses return `********` for `owner_name`.
-   Revealed responses decrypt only the Owner Name needed by the DataTable.
-   `owner_contact` is not selected and is never included in the normal DataTable JSON response.
-   The query selects only Tax Code, BIN, ward, due year and Owner Name required by the table.
-   Revealed list requests are written to the PII audit log without recording the decrypted value.
-   The `pii.no-cache` middleware prevents browser and intermediary caching of the DataTable response.

The standard Property Tax CSV export remains non-PII. It exports Tax Code, BIN, ward and due years only. The unmatched-record export contains Tax Code and last payment date only. Plaintext owner data must use the dedicated password-confirmed PII export.

### Dedicated Property Tax PII Export

The dedicated export accepts a CSV containing a `tax_code` header and one Tax Code per row. The uploaded file is validated in the browser for usability and validated again on the server as the security boundary.

`PropertyTaxPiiExportController::export()` requires:

-   `List Property Tax Collection`
-   `View Property Tax Owner PII`
-   `Unlock Property Tax Owner PII`
-   `Export Property Tax Owner PII`
-   Successful current-password verification for the export request

The reveal session alone is not enough to export. The password must be re-entered for the export operation.

| Input/Output | Behavior |
|--------------|----------|
| CSV header | Must contain `tax_code`. |
| Validation | Tax Codes are normalized to uppercase, validated, deduplicated and limited by `PII_EXPORT_MAX_TAX_CODES`. |
| Export columns | `tax_code`, `bin`, `owner_name`, `owner_contact`, `last_payment_date`, `status`. |
| `matched` status | The Tax Code exists and is linked to a Building BIN. |
| `unmatched` status | The Tax Code exists in Property Tax but has no linked Building BIN. |
| `not_found` status | The requested Tax Code was not found in `taxpayment_info.tax_payments`. |
| File security | The streamed response uses no-store headers and spreadsheet-formula prefixes are escaped. |

The downloaded CSV contains sensitive plaintext. It must be moved to approved encrypted storage, shared only through an approved secure channel and deleted when no longer required.

### Permissions and Routes

Permissions are created by `database\seeders\PropertyTaxPiiPermissionsSeeder.php`:

-   `View Property Tax Owner PII`
-   `Unlock Property Tax Owner PII`
-   `Export Property Tax Owner PII`

| Method | Route | Route name | Purpose |
|--------|-------|------------|---------|
| POST | `tax-payment/owner-pii/unlock` | `tax-payment-pii.unlock` | Password-confirmed five-minute reveal; limited to five attempts per minute. |
| POST | `tax-payment/owner-pii/lock` | `tax-payment-pii.lock` | Immediately removes the Property Tax PII session. |
| POST | `tax-payment/owner-pii/export` | `tax-payment-pii.export` | Password-confirmed Tax Code PII export with rate limiting and no-cache protection. |
| GET | `tax-payment/data` | `tax-payment.getData` | Server-side DataTable response with masking or authorized reveal. |

### Audit and Response Protection

Property Tax PII activity is recorded through `app\Services\OwnerPiiAuditService.php` in `auth.owner_pii_audit_logs`. Recorded events include password failure, reveal grant, revealed list access, manual lock, export success and export failure.

Audit context can contain operational metadata such as user, surface, page position and export counts. It must not contain owner values, passwords, encryption keys, ciphertext, request payloads, session contents or tokens. `OwnerPiiAuditService` removes forbidden sensitive context keys before persisting an event.

The DataTable applies `pii.no-cache`. The dedicated export route also applies `pii.no-cache`, and its response explicitly includes `Cache-Control: no-store`, `Pragma: no-cache`, an immediate expiry and `X-Content-Type-Options: nosniff`.

### Configuration and Migration Commands

Property Tax owner PII uses the shared settings in `config\pii.php`, including:

-   `PII_KEY_PROVIDER`
-   `PII_ACTIVE_KEY_VERSION`
-   `PII_KEY_V1_FILE`
-   `PII_ALLOW_LEGACY_PLAINTEXT`
-   `PII_AUDIT_ENABLED`
-   `PII_EXPORT_MAX_FILE_KB`
-   `PII_EXPORT_MAX_TAX_CODES`

The reveal duration currently uses `config('pii.unlock_minutes')`, which is set to five minutes in `config\pii.php`.

The key file must remain outside the project, database and source control. The same active key decrypts the protected Building, Application and Property Tax records. Losing the key without a verified backup makes the encrypted values unrecoverable.

| Command | Purpose |
|---------|---------|
| `php artisan pii:check-configuration` | Validates the configured provider, active key and encryption round trip without displaying key material. |
| `php artisan pii:encrypt-existing-property-tax --dry-run` | Counts legacy plaintext Property Tax owner fields without changing the database. |
| `php artisan pii:encrypt-existing-property-tax` | Encrypts non-null fields that are not already encrypted, then rebuilds Property Tax status data. |
| `php artisan pii:verify-property-tax-encryption` | Confirms all non-null Property Tax owner values are encrypted and decryptable without printing them. |

Recommended migration sequence:

1. Back up the database and external PII key through the approved secure process.
2. Run `php artisan pii:check-configuration`.
3. Run `php artisan pii:encrypt-existing-property-tax --dry-run` and review the counts.
4. Run `php artisan pii:encrypt-existing-property-tax`.
5. Run `php artisan pii:verify-property-tax-encryption`.
6. Test masked list, password reveal, manual/automatic lock, standard export and selected Tax Code PII export.
7. Keep `PII_ALLOW_LEGACY_PLAINTEXT=false` after all protected module backfills pass verification, then run `php artisan config:clear`.

### Automated Tests

The main Property Tax PII test is:

-   `tests\Unit\PropertyTaxPiiTest.php`

It verifies model encryption, explicit presenter masking/reveal, independent and revocable Property Tax access, Tax Code deduplication and required CSV header validation. Shared behavior is also covered by the PII encryption, audit and response-cache protection tests used by the other protected modules.
