# Kaniz Global Trade B2B + B2C Marketplace

Enterprise-grade global B2B and B2C marketplace platform.

This project is going into a global trade platform with supplier verification, RFQ, quotation, purchase order, proforma invoice, logistics, freight, container tracking, currency exchange, escrow, trade finance, and international commerce workflows.

---

## Project Overview

Kaniz Global Trade is designed as a hybrid marketplace:

- B2C marketplace for normal retail customers
- B2B marketplace for companies, suppliers, manufacturers, wholesalers, distributors, importers, and exporters

The project keeps the original an eCommerce CMS B2C system intact and adds enterprise B2B modules as extension layers.

---

## Main Goals

- Build a global B2B + B2C marketplace
- Keep existing B2C checkout and payment system stable
- Add Alibaba-style B2B trade workflow
- Add supplier discovery and public supplier profiles
- Add RFQ, quotation, PO, PI, negotiation, shipment, freight, escrow, and trade finance
- Support global currency and exchange-rate infrastructure
- Support international carrier and freight forwarder integrations
- Prepare the system for future search, AI, API, and mobile app phases

---

## Technology Stack

### Backend

- Laravel
- PHP 8+
- MySQL / MariaDB
- Redis
- Laravel Queue
- Laravel Scheduler
- eCommerce CMS core

### Frontend

- Blade
- Bootstrap
- jQuery
- AJAX

### Integrations

- ExchangeRate-API
- DHL
- FedEx
- UPS
- Aramex
- Maersk
- MSC
- CMA CGM
- Hapag-Lloyd
- COSCO
- Evergreen
- ONE
- DP World
- Freightos
- Flexport

---

## Core B2C Features

The original eCommerce CMS features remain available:

- Customer registration and login
- Product catalog
- Product details
- Cart
- Checkout
- Orders
- Wishlist
- Reviews
- Coupons
- Wallet
- Seller marketplace
- Vendor dashboard
- Existing payment gateways
- Existing offline payment
- Existing order management
- Existing shipping flow
- Existing admin panel

B2C checkout, payment, seller payout, wallet, and order pipeline are not replaced.

---

## Enterprise B2B Features

### Company Management

- B2B company profile
- Buyer company
- Supplier company
- Manufacturer
- Distributor
- Wholesaler
- Retailer
- Importer
- Exporter
- Company KYC
- Company document upload
- Company verification
- Admin approval/rejection
- Verified supplier badge

---

### Company Team and Roles

Each company can have multiple members.

Supported roles:

- Owner
- Admin
- Procurement Manager
- Sales Manager
- Finance Manager
- Logistics Manager
- Viewer

The system supports company context, so one user can switch between multiple companies.

---

### Supplier Directory

- Public supplier directory
- Supplier public profile
- Factory capability
- Business scope
- Main markets
- Production capacity
- Certifications
- Supplier categories
- Featured supplier
- Verified supplier badge
- Supplier profile score
- Request Quote button
- Request Sample button

---

### RFQ System

RFQ means Request for Quotation.

Supported:

- Buyer creates RFQ
- Product-based RFQ
- Category-based RFQ
- Targeted supplier RFQ
- Supplier receives RFQ
- Supplier submits quotation
- Buyer compares quotations
- Buyer accepts/rejects quotation
- RFQ status workflow
- RFQ attachments
- RFQ negotiation

---

### Quotation System

- Supplier quotation
- Price
- Currency
- MOQ
- Lead time
- Shipping terms
- Payment terms
- Attachments
- Pending / accepted / rejected / withdrawn status
- Duplicate quotation prevention
- Quotation edit before acceptance
- Buyer quotation comparison
- Accepted quotation generates purchase order

---

### Purchase Order

- Auto-generated PO from accepted quotation
- Buyer company
- Supplier company
- RFQ reference
- Quotation reference
- Payment terms
- Shipping terms
- Incoterms
- Delivery deadline
- PO status workflow
- Supplier accept/reject
- Finance board integration

---

### Proforma Invoice

- Supplier generates PI from accepted PO
- Product line items
- Tax
- Shipping
- Discount
- Grand total
- Valid until
- Buyer finance board
- Supplier finance board
- Payment and escrow relation

---

### Negotiation and Messaging

- RFQ negotiation thread
- Quotation negotiation
- Purchase order discussion
- Proforma invoice discussion
- Message attachments
- Timeline
- Buyer and supplier communication history

---

### Audit Logs

The system tracks important actions:

- RFQ created
- Quotation submitted
- Quotation accepted
- Purchase order generated
- Purchase order accepted/rejected
- Proforma invoice generated
- Payment created
- Escrow funded
- Escrow released
- Refund
- Dispute
- Team member invited
- Role changed
- Shipment updated
- Freight event synced

---

## Trade Services

### Sample Orders

- Buyer requests sample
- Supplier accepts sample request
- Shipping quote
- Payment
- Shipment
- Delivery tracking

---

### Trade Documents

Supported document types:

- Commercial Invoice
- Packing List
- Certificate of Origin
- Bill of Lading
- Air Waybill
- Inspection Report
- Insurance Certificate
- Quality Certificate
- Export License
- Import License
- HS Code Declaration
- Customs Clearance Document

Documents can be attached to:

- Purchase Order
- Proforma Invoice
- Shipment
- Freight Quote
- Container Shipment

---

### Incoterms

Supported Incoterms:

- EXW
- FOB
- CIF
- DAP
- DDP
- FCA
- CPT
- CIP

Used in:

- RFQ
- Quotation
- Purchase Order
- Proforma Invoice
- Shipping Quote
- Freight Quote

---

## Shipping and Carrier Tracking

### Supported Carrier Drivers

- DHL
- FedEx
- UPS
- Aramex
- Manual Carrier
- Custom Carrier

### Carrier Features

- Provider setup
- API credentials
- Webhook secret
- Test connection
- Shipment creation
- Tracking number
- Carrier reference
- Live tracking
- Shipment timeline
- Webhook handling
- Status normalization
- Sync command
- Queue job
- Manual fallback

### Shipment Statuses

- Preparing
- Picked Up
- Export Customs
- In Transit
- Import Customs
- Out For Delivery
- Delivered
- Exception
- Delayed
- Cancelled

---

## Freight and Container Logistics

### Supported Freight Forwarders

- Maersk
- MSC
- CMA CGM
- Hapag-Lloyd
- COSCO
- Evergreen
- ONE
- DP World
- Freightos
- Flexport
- Custom Freight Forwarder

### Freight Modes

- Sea Freight
- Air Freight
- Rail Freight
- Truck Freight
- Multimodal Freight

### Service Types

- Port-to-Port
- Door-to-Port
- Port-to-Door
- Door-to-Door

### Container Types

- 20GP
- 40GP
- 40HQ
- 45HQ
- LCL
- Open Top
- Flat Rack
- Reefer
- Tank

---

### Freight Features

- Freight quote request
- Forwarder management
- Port management
- Container shipment
- Bill of Lading tracking
- Booking number
- Container number
- Vessel name
- Voyage number
- ETA
- ETD
- ATA
- Customs status
- Container timeline
- Freight sync command
- Freight webhook

---

### Port Management

Admin can manage:

- Port name
- Country
- City
- UN/LOCODE
- Port code
- Port type
- Latitude
- Longitude
- Timezone
- status
- Bulk import/export

---

### Container Tracking Events

- Gate In
- Loaded on Vessel
- Vessel Departed
- Transshipment Arrived
- Transshipment Departed
- Vessel Arrived
- Customs Hold
- Customs Cleared
- Gate Out
- Delivered
- Delayed
- Exception

---

## Freight Cost Management

The platform supports transparent freight cost breakdown.

Cost components:

- Base freight cost
- Fuel surcharge
- Port handling charge
- Terminal handling charge
- Documentation fee
- Customs clearance fee
- Customs duty
- VAT / GST
- Insurance
- Warehouse charge
- Pickup cost
- Delivery cost
- Inspection fee
- Demurrage
- Detention
- Miscellaneous fee
- Platform service fee
- Forwarder margin
- Supplier margin
- Discount
- Tax
- Total landed cost

---

### Freight Pricing Rules

Pricing rules can be configured by:

- Forwarder
- Freight mode
- Service type
- Origin country
- Destination country
- Container type
- Incoterm
- Weight range
- Volume range
- Base price
- Price per kg
- Price per CBM
- Fuel surcharge
- Platform fee
- Currency

---

### Landed Cost Calculator

The landed cost calculator supports:

- Product cost
- Freight cost
- Insurance
- Customs duty
- VAT / GST
- Customs fee
- Port charges
- Local delivery
- Currency conversion
- Cost per unit
- Margin estimate
- Suggested selling price

---

### HS Code Management

Admin can manage:

- HS Code
- Description
- Country
- Duty percentage
- VAT / GST percentage
- Restrictions
- Dangerous goods flag
- Required documents

HS code data can be used for customs cost estimation.

---

## Integration Management

Carrier and freight integrations include:

- API credentials
- Webhook URL
- Tracking webhook URL
- Shipment webhook URL
- Pickup webhook URL
- Callback URL
- Test connection
- Credential verification
- Webhook verification
- Sample webhook
- Webhook secret generation
- Event subscriptions
- Health metrics
- API logs
- Success rate
- Failed request count
- Last API call
- Last webhook received
- Last sync time

Webhook URLs are generated by Laravel route helpers and become visible after credentials are configured.

---

## Global Currency Infrastructure

The system extends the existing eCommerce currency system.

### Features

- Base currency
- Display currency
- ExchangeRate-API support
- Manual rate driver
- Custom rate driver
- Exchange-rate history
- FX snapshot
- Automatic sync
- Admin test connection
- Safe fallback to cached rates

### Currency Tables

- currency_api_settings
- currency_exchange_rates
- currency_rate_history

### Currency Sync

```bash
php artisan currency:sync
```

Currency API key should be stored through the admin currency settings and encrypted in the database.

Optional environment fallback:

```env
EXCHANGE_RATE_API_KEY=
```

---

## Trade Finance

The system extends the existing eCommerce payment ecosystem instead of replacing it.

### Existing Payment System Reused

- PaymentController
- CheckoutController
- OrderController
- Wallet
- Offline payment
- Bank transfer
- Seller payout
- Commission
- Existing gateway configuration
- Existing webhook system

No duplicate payment gateway implementation is created.

---

### B2B Payment Transactions

Supported references:

- Purchase Order
- Proforma Invoice
- Freight Quote
- Shipping Quote
- Sample Order

Stored data:

- Buyer company
- Supplier company
- Payment gateway
- Payment method
- Currency
- Amount
- FX snapshot
- Gateway reference
- Status
- Paid date
- Verified by

---

### Escrow

Escrow workflow:

1. Buyer pays through existing gateway
2. Payment is held in escrow
3. Supplier ships goods
4. Buyer confirms delivery
5. Funds are released
6. Supplier receives settlement

Supported:

- Full release
- Partial release
- Manual release
- Automatic release
- Refund
- Dispute hold
- Escrow expiry

---

### Milestone Payments

Example milestone structure:

- 30% Deposit
- 40% Production
- 30% Delivery

Each milestone supports:

- Payment transaction
- Escrow
- Release
- Notification
- Audit log

---

### Letter of Credit

LC workflow:

- LC Request
- Bank Review
- Approved
- Documents Upload
- Shipment
- LC Release
- Completed

Stored fields:

- LC number
- Issuing bank
- Advising bank
- Expiry
- Amount
- Currency
- Required documents
- Status

---

### Settlement

Supported settlement methods:

- Wallet
- Bank Transfer
- Wise
- Payoneer
- Manual Settlement

Settlement data:

- Settlement status
- Settlement reference
- Settlement fees
- Settlement history

---

### Dispute and Refund

Dispute types:

- Late shipment
- Wrong product
- Damaged product
- Payment issue
- Document issue
- Refund request

Supported:

- Evidence upload
- Messages
- Timeline
- Escrow hold
- Admin decision
- Full refund
- Partial refund
- Manual refund
- Gateway refund

---

## Admin Dashboards

Admin can monitor:

- B2B companies
- Verified suppliers
- RFQs
- Quotations
- Purchase orders
- Proforma invoices
- Shipments
- Freight quotes
- Container shipments
- Trade documents
- Freight forwarders
- Carriers
- Ports
- HS codes
- Currency sync
- Exchange rates
- Escrow balance
- Trade finance
- Disputes
- Settlements
- API health

---

## Buyer Dashboard

Buyer can manage:

- Company profile
- Company team
- RFQs
- Received quotations
- Purchase orders
- Proforma invoices
- Payments
- Escrow
- Shipments
- Freight quotes
- Container tracking
- Trade documents
- Disputes
- Supplier profiles

---

## Supplier Dashboard

Supplier can manage:

- Company profile
- Public supplier profile
- Certifications
- Wholesale products
- RFQs
- Quotations
- Purchase orders
- Proforma invoices
- Sample requests
- Shipments
- Freight quotes
- Container shipments
- Trade documents
- Finance dashboard
- Settlements

---

## Permissions

Permission system is company-team based.

### Owner / Admin

- Manage company
- Manage team
- Approve actions
- Release escrow
- Manage supplier profile
- Manage RFQ, PO, PI, finance, logistics

### Procurement Manager

- Create RFQ
- Manage buyer-side PO
- View quotation
- Manage supplier selection

### Sales Manager

- Submit quotation
- Manage supplier-side PO
- Manage supplier profile

### Finance Manager

- Verify payment
- Manage escrow
- Manage settlement
- Manage invoice
- View financial dashboard

### Logistics Manager

- Manage freight
- Manage shipment
- Manage documents
- Manage customs
- Track containers

### Viewer

- Read-only access

---

## Database Stabilization

This project includes migration baseline stabilization for eCommerce legacy schema.

Clean test databases are supported with minimal legacy baseline tables where needed.

Verified commands:

```bash
php artisan migrate:fresh --seed --force
php artisan route:list
php artisan test
php artisan test --filter=B2B
```

Expected result:

- Migration passes
- Route list passes
- B2B tests pass
- B2C smoke tests pass

---

## Installation

Clone project:

```bash
git clone https://github.com/your-username/kaniz-global-trade-b2b-b2c.git
cd kaniz-global-trade-b2b-b2c
```

Install dependencies:

```bash
composer install
npm install
```

Create environment file:

```bash
cp .env.example .env
```

Generate key:

```bash
php artisan key:generate
```

Run migrations and seed:

```bash
php artisan migrate --seed
```

Build frontend assets:

```bash
npm run build
```

Run development server:

```bash
php artisan serve
```

---

## Environment Setup

Important environment variables:

```env
APP_NAME="Kaniz Global Trade"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kaniz_global_trade
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
CACHE_DRIVER=file
SESSION_DRIVER=file

EXCHANGE_RATE_API_KEY=
```

---

## Queue

Run queue worker:

```bash
php artisan queue:work
```

Production:

```bash
php artisan queue:work --tries=3
```

---

## Scheduler

Local:

```bash
php artisan schedule:work
```

Production cron:

```bash
* * * * * php /path/to/project/artisan schedule:run >> /dev/null 2>&1
```

---

## Important Commands

Clear cache:

```bash
php artisan optimize:clear
```

Run all tests:

```bash
php artisan test
```

Run B2B tests only:

```bash
php artisan test --filter=B2B
```

Show routes:

```bash
php artisan route:list
```

Currency sync:

```bash
php artisan currency:sync
```

Shipment sync:

```bash
php artisan b2b:shipments:sync
```

Freight sync:

```bash
php artisan b2b:freight:sync
```

Trade finance processor:

```bash
php artisan b2b:trade-finance:process
```

---

## Testing Status

The project baseline has been verified with:

```bash
php artisan migrate:fresh --seed --force
php artisan route:list
php artisan test --filter=B2B
php artisan test
```

Latest stable status:

- Migration: passed
- Route list: passed
- B2B tests: passed
- Full tests: passed
- B2C smoke tests: passed

---

## Production Deployment Notes

Recommended production services:

- Nginx or Apache
- PHP-FPM
- MySQL or MariaDB
- Redis
- Supervisor
- Cron
- SSL certificate
- Queue worker
- Scheduler
- Daily database backup

Recommended Laravel commands:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Supervisor Example

```ini
[program:kaniz-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/kaniz-global-trade/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/kaniz-global-trade/storage/logs/worker.log
```

---

## Security Notes

- Never commit `.env`
- Never hardcode API keys
- Store API keys encrypted in database
- Use HTTPS in production
- Use strong webhook secrets
- Rotate integration keys regularly
- Do not expose carrier/freight credentials
- Do not log sensitive payment data
- Keep vendor patches tracked carefully
- Back up database before migrations

---

## Git Baseline Commit

Before Phase 9:

```bash
git status --short
git add .
git commit -m "feat: complete enterprise b2b marketplace foundation"
git log --oneline -1
```

---

## Completed Roadmap

| Phase | Module | Status |
|---|---|---|
| Phase 1 | B2B Company + KYC | Complete |
| Phase 2 | RFQ + Quotation | Complete |
| Phase 2.5 | RFQ Hardening | Complete |
| Phase 3 | PO + PI + Negotiation | Complete |
| Phase 4 | Company Team + Roles | Complete |
| Phase 4.5 | Company Context | Complete |
| Phase 5 | Supplier Directory | Complete |
| Phase 6 | Trade Services | Complete |
| Phase 6.5 | Feature Tests | Complete |
| Phase 6.6 | Stabilization | Complete |
| Phase 7 | Carrier + Live Tracking | Complete |
| Phase 7.5 | Carrier API Integration | Complete |
| Phase 7.6 | Freight + Container Logistics | Complete |
| Phase 7.7 | Freight UI + Cost Management | Complete |
| Phase 8A | Currency + Payment Foundation | Complete |
| Phase 8B | Trade Finance Workflow | Complete |
| Phase 8C | Migration/Test Baseline | Complete |

---

## Next Roadmap

### Phase 9: Enterprise Search and Discovery

Planned:

- OpenSearch
- Meilisearch
- Elasticsearch
- Universal search
- Product search
- Supplier search
- Freight search
- Container search
- HS code search
- Search analytics

---

### Phase 10: AI Commerce Engine

Planned:

- AI supplier matching
- AI RFQ assistant
- AI product recommendation
- AI price recommendation
- AI freight recommendation
- AI HS code suggestion
- AI incoterm suggestion
- AI trade assistant

---

### Phase 11: Enterprise UX Modernization

Planned:

- Modern admin dashboard
- Modern buyer dashboard
- Modern supplier dashboard
- Finance dashboard
- Freight dashboard
- KPI cards
- Charts
- Timeline UI
- Professional table filters

---

### Phase 12: B2B-first Homepage Redesign

Planned:

- Global B2B hero
- Search products, suppliers, manufacturers
- Request Quote CTA
- Become Supplier CTA
- Featured suppliers
- Verified manufacturers
- Wholesale categories
- Trade services
- B2C product sections

---

### Phase 13: Public REST API

Planned:

- API versioning
- Buyer API
- Supplier API
- Product API
- RFQ API
- Shipment API
- Finance API

---

### Phase 14: Mobile API

Planned:

- Buyer app API
- Supplier app API
- Admin API
- Push notifications

---

### Phase 15: Scalability and Production Hardening

Planned:

- Redis caching
- OpenSearch optimization
- CDN
- Load testing
- Horizontal scaling
- Kubernetes-ready deployment

---

## Project Philosophy

This project does not replace eCommerce CMS.

It extends eCommerce CMS into a global B2B trade platform while preserving the existing B2C foundation.

The main rule is:

> Keep B2C stable. Add B2B as enterprise extension layers.

---

## Author

**Md. Shahanur Islam Shagor**

Enterprise B2B Marketplace Architecture

Global Trade Platform

Built with Laravel & Node.js ❤️

---