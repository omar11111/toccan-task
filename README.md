# Order & Payment Management API

A Laravel-based REST API for managing orders and payments, built with extensibility and clean architecture as primary goals. New payment gateways can be added with **zero changes to existing code**.

---

## Tech Stack

- **Laravel 11**
- **MySQL** (UUID v7 primary keys)
- **JWT Authentication** (`tymon/jwt-auth`)
- **PHPStan** (with Larastan) for static analysis

---

## Setup Instructions

### 1. Clone & Install

```bash
git clone <repo-url>
cd order-payment-api
composer install
```

### 2. Environment

```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

Then fill in the gateway credentials in `.env` (any placeholder value works, since gateways are simulated):

```env
CREDIT_CARD_GATEWAY_API_KEY=test_cc_api_key
CREDIT_CARD_GATEWAY_SECRET=test_cc_secret

PAYPAL_CLIENT_ID=test_paypal_client_id
PAYPAL_SECRET=test_paypal_secret
```

### 3. Database

```bash
php artisan migrate
```

### 4. Run

```bash
php artisan serve
```

### 5. Run Tests

```bash
php artisan test
```

50 tests covering authentication, order lifecycle, payment processing, idempotency, gateway resolution, and ownership isolation.

---

## API Endpoints

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| POST | `/api/auth/register` | — | Register a new user |
| POST | `/api/auth/login` | — | Login, returns JWT |
| POST | `/api/auth/logout` | ✅ | Invalidate current token |
| GET | `/api/auth/me` | ✅ | Current authenticated user |
| GET | `/api/orders` | ✅ | List orders (supports `?status=` filter, `?per_page=`) |
| POST | `/api/orders` | ✅ | Create an order |
| GET | `/api/orders/{order}` | ✅ | Order details |
| PUT | `/api/orders/{order}` | ✅ | Update order (full item replacement if `items` provided) |
| DELETE | `/api/orders/{order}` | ✅ | Delete order (only if it has no payments) |
| PATCH | `/api/orders/{order}/confirm` | ✅ | Confirm a pending order |
| PATCH | `/api/orders/{order}/cancel` | ✅ | Cancel an order |
| GET | `/api/payments` | ✅ | List all payments for the current user |
| GET | `/api/orders/{order}/payments` | ✅ | List payments for a specific order |
| POST | `/api/orders/{order}/payments` | ✅ + `Idempotency-Key` header | Process a payment |

A full Postman collection with example requests/responses is included: `Order-Payment-API.postman_collection.json`.

Auth and registration endpoints are rate-limited to 5 requests/minute per IP.

---

## Architecture Decisions

### 1. UUID v7 Primary Keys

All models use UUID v7 (timestamp-prefixed, sortable) instead of auto-increment integers.

- **Why not plain integers?** Sequential IDs are guessable, which is a real concern for a payments system (IDOR risk on order/payment IDs).
- **Why not UUID v4?** Fully random UUIDs cause index fragmentation on InnoDB (the primary key is the clustered index, and every secondary index stores a copy of it).
- **UUID v7** keeps IDs unguessable while remaining roughly sequential by creation time, avoiding the fragmentation cost.

### 2. Payment Gateways: Strategy + Config-Driven Resolver

```
PaymentGatewayInterface (charge)
RefundableGatewayInterface (refund — optional, ISP-compliant)
    ├── CreditCardGateway
    └── PaypalGateway

config/payments.php → maps gateway key to class + secrets (from .env)
PaymentGatewayResolver → resolves via Laravel's container, reading from config
```

**Adding a new gateway requires:**
1. A new class implementing `PaymentGatewayInterface` (and `RefundableGatewayInterface` if it supports refunds).
2. One new entry in `config/payments.php`.

**Zero modification to `PaymentGatewayResolver` or any existing class.** This is true Open/Closed Principle compliance — the resolver has no hardcoded knowledge of which gateways exist.

Gateways currently return plain arrays (`status`, `gateway_reference`, `raw_response`, `failure_reason`) rather than DTOs, by deliberate choice to keep the codebase simple for this task's scope.

### 3. Interface Segregation on Gateways

Not all gateways support refunds (e.g., a hypothetical Cash-on-Delivery gateway). Splitting `charge()` and `refund()` into separate interfaces means a gateway only implements what it actually supports, with `instanceof RefundableGatewayInterface` checks at the call site — instead of forcing every gateway to implement a `refund()` method it can't honor.

### 4. Order Status vs. Payment Status — Kept Strictly Separate

```php
OrderStatus:   pending → confirmed → paid
                       ↘ cancelled
PaymentStatus: pending → successful
                       ↘ failed
```

This was a deliberate correction made during design review. A failed payment attempt **never** changes the Order's status — the order stays `confirmed`, allowing retries. Only a *successful* payment moves the order to `paid`. Mixing these concerns (e.g., an Order status of `payment_failed`) would cause unnecessary status churn on retries and conflate two distinct domain concepts.

`paid` and `cancelled` are terminal states — no further transitions are allowed from them (enforced centrally in `OrderStatus::allowedTransitions()`).

### 5. Layering: Controller → Service → Gateway

Controllers only handle HTTP concerns (request/response shaping). All business logic — status checks, locking, idempotency, transitions — lives in `OrderService` and `PaymentService`. This keeps controllers thin and makes the business logic reusable and testable independent of HTTP.

### 6. Concurrency & Idempotency

Two distinct problems, solved with two distinct mechanisms:

| Problem | Mechanism |
|---|---|
| Same logical request retried (timeout, double-click) | **Idempotency-Key** header (required, client-generated, no fallback) |
| Two genuinely different requests racing on the same order | **Pessimistic locking** (`lockForUpdate()` inside `DB::transaction()`) |

Processing flow:
1. Idempotency check — if the key was already used, return the existing payment (no reprocessing).
2. Resolve the gateway (no lock needed for this).
3. **Lock** the order row, validate it's `confirmed` (not already `paid`, not still `pending`).
4. Create the `Payment` record as `pending` **before** calling the gateway — this guarantees a durable record of the attempt even if the gateway call fails unexpectedly.
5. Call the gateway, update the payment's final status.
6. Only on success, transition the order to `paid`.

**Known trade-off:** the gateway call happens *inside* the database transaction. In a real system with a slow external gateway, this would hold the row lock (and a DB connection) for the duration of the network call. The alternative — splitting into two transactions around the gateway call — removes that problem but reopens a window for a duplicate charge if two different idempotency keys hit the same order in that gap. Given this task simulates instant gateway calls, the single-transaction approach was kept; the trade-off is documented here rather than silently accepted.

**On testing concurrency:** genuine race conditions (two simultaneous requests at the database level) are difficult to reproduce deterministically in Laravel's synchronous Feature tests. Test coverage instead verifies the *resulting business rules* — idempotency replay, rejection of payments on already-paid orders, rejection on non-confirmed orders — which are the observable guarantees that the locking mechanism is designed to produce. A true concurrent-request test would require parallel process execution or a dedicated database-level concurrency testing tool.

### 7. No Product Catalog

The task describes order items as `(product name, quantity, price)` sent directly with the order — there is no requirement for product management (CRUD, inventory, etc.). Adding a `products` table would be scope creep beyond the literal requirements. Items are stored as a denormalized snapshot in `order_items`, which also correctly preserves historical pricing even if a "catalog" were added later.

### 8. Snapshot Fields on Orders

`orders.customer_name` and `orders.customer_email` are stored as a snapshot at creation time, separate from the authenticated `users` table. This matches the task's explicit requirement to "accept user details" per order, and ensures historical orders aren't silently altered if a user later updates their account profile.

### 9. Authorization

Ownership checks (`order.user_id === current user`) are a single rule with no role hierarchy (no admin/moderator distinction in scope). This is handled via a small `AuthorizesOrderOwnership` trait used only by the controllers that need it — not a full Policy class, which would be unjustified ceremony for a single equality check. If role-based rules are introduced later, this is the natural extraction point into a proper `OrderPolicy`.

### 10. Status Transitions as Explicit Commands, Not Data Updates

`PATCH /orders/{order}/confirm` and `/cancel` are separate endpoints rather than `PUT /orders/{order}` with a `status` field. A status transition is a **business action** with potential side effects (notifications, audit logs, events) — treating it as a plain data field update would conflate "replace this resource's data" (PUT semantics) with "execute this business command," making future side effects awkward to attach correctly.

---

## Assumptions

- No product catalog; items are freeform data per order, as worded in the task.
- A single payment per gateway charge; partial payments are out of scope.
- Refunds are implemented at the gateway interface level (`RefundableGatewayInterface`) but no API endpoint exposes them yet, as the task does not require a refund endpoint.
- Gateway credentials are simulated; no real external HTTP calls are made.
- Rate limiting on auth endpoints (5 req/min) is a reasonable default, not a hard requirement from the task — easily adjustable in `routes/api.php`.

---

## Project Structure

```
app/
├── Enums/                     OrderStatus, PaymentStatus
├── Exceptions/                Domain exceptions (mapped to proper HTTP codes in bootstrap/app.php)
├── Http/
│   ├── Controllers/Api/       Thin HTTP controllers
│   ├── Controllers/Concerns/  AuthorizesOrderOwnership trait
│   ├── Requests/              Form Request validation
│   ├── Resources/             API response shaping
│   └── Middleware/            EnsureIdempotencyKeyIsPresent
├── Models/                    Eloquent models + state transition methods
├── PaymentGateways/           Contracts, concrete gateways, Resolver
├── Providers/                 PaymentGatewayServiceProvider
└── Services/                  OrderService, PaymentService (business logic)

tests/
├── Unit/                      Gateway logic, Resolver, Model transitions, PaymentService (mocked)
└── Feature/                   Full HTTP-level flows (Auth, Orders, Payments, Idempotency)
```
