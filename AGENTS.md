# AGENTS.md

# AEFS v2 — Repository Instructions for Codex

This file is the authoritative working contract for AI-assisted development in the AEFS v2 repository.

Codex must read this file before making changes.

If code in the repository conflicts with this document, do not silently invent a compromise. Inspect the surrounding implementation, identify whether the code is legacy or current, and preserve the current AEFS architecture unless the user explicitly requests an architectural change.

---

## 1. Project identity

AEFS v2 is a custom CRM and event-management application for All Events Forever Sure.

The application is being rebuilt as a maintainable custom PHP application with its own framework and application layer.

### Technology

- PHP 8.4
- Composer
- PSR-4 autoloading
- PSR-12 coding style
- SOLID principles
- Constructor Dependency Injection
- PDO / custom database abstraction
- Custom MVC-style architecture
- Custom router
- Custom dependency-injection container
- Custom request/response layer
- Custom view engine
- Custom authentication and authorization
- Custom middleware
- Custom session and flash-message handling

### Explicitly not used

Do not introduce:

- Laravel
- Symfony
- Twig
- Blade
- an external MVC framework
- Eloquent
- Doctrine ORM
- global service locators as a replacement for constructor injection
- a JavaScript framework unless explicitly requested
- Bootstrap as a new dependency

Existing functionality should remain framework-native to AEFS.

---

# 2. Primary development principle

Before changing code, understand the existing implementation.

For every non-trivial task:

1. Inspect the relevant current files.
2. Inspect adjacent architecture and conventions.
3. Inspect database dependencies if persistence is involved.
4. Check routes and middleware if HTTP behavior is involved.
5. Check views and existing frontend conventions if UI behavior is involved.
6. Only then modify code.

Never generate a parallel architecture because the current implementation was not inspected carefully enough.

Examples of prohibited behavior:

- creating a second service for logic already owned by an existing service;
- creating a separate controller for one small action when the existing domain controller already owns that action;
- introducing a new repository style inconsistent with the current repositories;
- reintroducing old table names because they appear in historical code;
- inventing an `eventmanager` role because an old prototype referenced it;
- replacing working custom framework components with third-party equivalents.

---

# 3. Current project architecture

The repository is divided into a framework layer and an application layer.

## Framework/Core

Primary namespace:

```text
AEFS\
```

Core/framework code belongs under:

```text
src/
```

Do not move application-specific business logic into `src/`.

The Core owns generic infrastructure such as:

- container/autowiring;
- routing;
- HTTP request handling;
- HTTP responses;
- session;
- authentication infrastructure;
- middleware infrastructure;
- database infrastructure;
- view engine;
- view helpers;
- validation infrastructure where generic;
- framework exceptions/utilities.

## Application

Primary namespace:

```text
App\
```

Application-specific code belongs under:

```text
app/
```

Current major application directories include:

```text
app/
├── Controllers/
├── Http/
│   └── Requests/
├── Mappers/
├── Middleware/
├── Models/
├── Repositories/
├── Services/
├── Validators/
└── Views/
```

Routes live under:

```text
routes/
```

Database assets/migrations belong under:

```text
database/
```

Public web assets belong under the existing public asset structure.

Do not change these boundaries without explicit approval.

---

# 4. Layer responsibilities

AEFS v2 uses clear layer ownership.

## Controller

Controllers handle HTTP orchestration only.

Controllers may:

- read route parameters;
- read query parameters;
- read submitted form data;
- validate CSRF;
- instantiate/use request DTO-style objects;
- call services;
- choose a view;
- redirect;
- produce JSON for AJAX endpoints;
- set flash messages;
- translate expected failures into HTTP/UI feedback.

Controllers must not contain substantial business rules.

### Current controller conventions

Controllers normally extend:

```php
App\Controllers\BaseController
```

Typical constructor structure:

```php
public function __construct(
    ViewFactory $views,
    Request $request,
    private readonly SomeService $service
) {
    parent::__construct(
        $views,
        $request
    );
}
```

Use the current request object.

Typical route parameter:

```php
$id = (int) $this->request()->route('id', 0);
```

Typical POST input:

```php
$input = $this->request()->request->all();
```

Typical query input:

```php
$search = trim(
    (string) $this->request()->query->get('zoek', '')
);
```

Do not introduce old/global helpers such as:

```php
auth()
abort()
request()
redirect()
```

unless such a helper already exists in the current Core and the surrounding code actively uses it.

## HTTP Request objects

Application request-normalization classes belong under:

```text
app/Http/Requests/
```

Their purpose is to:

- normalize submitted input;
- convert booleans;
- trim strings;
- combine related form values;
- return a predictable application array.

They are not repositories and must not perform SQL.

## Validator

Domain/input validation belongs under:

```text
app/Validators/
```

Validators should:

- enforce field/domain invariants;
- throw clear exceptions for invalid input;
- not render views;
- not redirect;
- not perform unrelated persistence.

## Service

Business logic belongs in services.

Services may:

- coordinate repositories;
- enforce domain rules;
- run transactions;
- perform authorization-sensitive domain checks;
- create audit logs;
- trigger notification workflows;
- decide allowed state transitions.

A service should represent one coherent domain responsibility.

Do not split one domain into multiple overlapping services without a clear architectural reason.

## Repository

Repositories own database access for an application concept.

Repositories may:

- execute queries;
- insert/update/delete rows;
- provide read models;
- implement locking queries;
- count related rows;
- expose persistence operations required by services.

Repositories must not:

- render HTML;
- redirect;
- own HTTP request logic;
- silently implement business workflow that belongs in a service.

## Mapper

Mappers convert persistence representation to application models and vice versa.

Keep database-column knowledge in mappers/repositories rather than scattering it through controllers and views.

## Model

Models represent domain/read state.

Models may expose presentation-neutral derived behavior, for example:

- status checks;
- capacity calculations;
- date/time calculations;
- domain state helpers;
- display labels where this is already the project convention.

Models must not query the database.

## View

Views render already-prepared data.

Views may contain small display calculations, but must not query repositories or implement domain workflows.

---

# 5. Dependency Injection

Use constructor dependency injection.

The AEFS container/autowiring mechanism is the normal dependency-resolution mechanism.

Preferred:

```php
public function __construct(
    SomeRepository $repository,
    SomeValidator $validator
) {
}
```

Avoid:

```php
$repository = new SomeRepository(...);
```

inside controllers/services.

Avoid static service containers unless the current Core explicitly requires a static facade for that specific concern.

`AEFS\Core\Auth` is an existing project convention and may be used where current code uses it.

---

# 6. HTTP and response conventions

Controller actions return:

```php
AEFS\Core\Http\Response
```

Use the existing BaseController helpers for:

- views;
- redirects;
- success flash messages;
- error flash messages.

Use the existing response JSON capability for AJAX.

Do not emit raw headers or call `exit` from normal controllers.

For AJAX behavior:

- keep server-side authorization;
- keep CSRF validation;
- return structured JSON;
- use meaningful HTTP status codes;
- preserve a normal non-JavaScript POST/redirect fallback when practical.

A UI optimization must never bypass business rules because it uses AJAX.

---

# 7. CSRF

All state-changing browser requests must remain CSRF protected.

Views use the existing helper, for example:

```php
<?= $helpers->csrf->field() ?>
```

Controllers must validate the submitted token using the existing `CsrfHelper`/current project mechanism.

AJAX requests must submit the same CSRF token.

Never disable CSRF merely to simplify fetch/AJAX code.

---

# 8. Authentication and authorization

## Roles

The only currently defined application roles are:

```text
admin
lid
```

Do not invent additional roles unless explicitly requested.

In particular:

```text
eventmanager
```

is not currently a valid AEFS v2 role.

## Role ownership

Roles belong to the linked user account in:

```text
gebruikers
```

Roles do not belong directly to a member record in:

```text
leden
```

## Admin

An administrator may perform administration functionality such as:

- member administration;
- user approval and role management;
- event administration;
- shift administration;
- shift-registration decisions;
- presence administration.

## Member

A normal member:

- must not receive general member-management access;
- must not receive user-management access;
- may access permitted member-facing functionality;
- may view/edit their own profile where implemented;
- may register for eligible published events;
- must never assign themselves to a shift;
- may manage only their own registration where allowed.

## Middleware

Use existing middleware such as:

```php
AuthMiddleware::class
AdminMiddleware::class
GuestMiddleware::class
```

Keep authorization in routes and domain logic where appropriate.

Never rely only on hiding a button in a view.

---

# 9. Routing conventions

Routes are split by module under:

```text
routes/
```

Examples include:

```text
routes/auth.php
routes/members.php
routes/users.php
routes/events.php
routes/shifts.php
```

The central route loader is:

```text
routes/web.php
```

Before adding a new route:

1. inspect the module route file;
2. inspect route naming conventions;
3. inspect middleware conventions;
4. ensure route order cannot conflict with parameter routes.

Use route names consistently with the existing module.

State-changing operations must use POST or another appropriate mutation method supported by the router.

Do not use GET for:

- deleting;
- approving;
- cancelling;
- presence toggling;
- role changes;
- other mutations.

---

# 10. View engine and frontend conventions

AEFS v2 uses its own view engine.

Application views belong under:

```text
app/Views/
```

Do not create active duplicate views under unrelated legacy paths.

Views typically use the current helpers and layout system.

Typical layout extension:

```php
$this->extend(
    'layouts.app',
    [
        'title' => $title,
    ]
);
```

Typical sections:

```php
<?php $this->startSection('content'); ?>
...
<?php $this->endSection(); ?>
```

The existing system also supports view sections for page-local styles/scripts.

Use existing reusable components and helpers where they already solve the problem.

Examples:

- page headers;
- cards;
- empty states;
- form helpers;
- URL helper;
- asset helper;
- CSRF helper;
- validation/error renderer.

## Styling

The current interface is responsive and uses the AEFS visual language.

Requirements:

- desktop-first usage must remain comfortable;
- mobile/tablet layouts must remain usable;
- reporting pages must remain clearly readable and operable on tablets in both
  portrait and landscape orientation, with touch-friendly controls and without
  avoidable horizontal scrolling;
- use existing CSS variables;
- preserve AEFS branding;
- avoid hardcoding a second design system;
- avoid new Bootstrap dependencies;
- avoid making pages look like isolated mini-applications.

Page-specific CSS may be used in the established view section when appropriate.

## JavaScript

Use vanilla JavaScript unless explicitly instructed otherwise.

Prefer progressive enhancement:

- server-side flow remains valid;
- JavaScript improves efficiency;
- failure gives visible feedback.

For repeated inline actions such as presence toggling, avoid a full-page reload when an AJAX update is safe and already authorized.

Do not create a separate frontend framework for a small interaction.

---

# 11. Error handling and user feedback

Expected validation/domain failures should produce clear user-facing messages.

Use existing session flash mechanisms.

Typical pattern:

```php
Session::flash(
    '_errors',
    [
        'form' => [
            $throwable->getMessage(),
        ],
    ]
);
```

Do not expose:

- stack traces;
- SQL queries;
- credentials;
- secrets;
- internal filesystem details

to normal end users.

Development exceptions may still be handled by the existing framework exception layer.

---

# 12. Audit logging

AEFS v2 has an existing:

```text
App\Services\AuditLogService
```

Use that implementation rather than introducing another `AuditService`.

Important administration/domain mutations should be auditable where the current architecture expects it.

Examples:

- member changes;
- user changes;
- event changes;
- shift changes;
- shift-registration state changes;
- presence changes where currently audited.

Never silently delete historical state that is needed for audit/history.

---

# 13. Database safety contract

This section is critical.

## Absolutely protected data

Existing data in these tables must never be lost:

```text
leden
gebruikers
```

Schema evolution is possible when explicitly needed, but existing member/user rows and their meaningful data must be preserved.

Before any risky database migration affecting these tables:

1. inspect existing schema;
2. inspect existing data relationships;
3. design migration;
4. explicitly preserve data;
5. avoid destructive assumptions.

## General migration principle

Database migrations must favor:

- deterministic transformations;
- data preservation;
- foreign-key integrity;
- unique constraints that reflect domain rules;
- reversible/inspectable transitions for major migrations.

Never blindly `DROP TABLE` on a table containing meaningful production data.

When replacing a legacy table, keeping a temporary `_legacy` copy during verification is acceptable.

Legacy backups must not be used by active application code after migration.

---

# 14. Current core application tables and terminology

Current relevant domain naming includes:

```text
leden
gebruikers
evenementen
event_inschrijvingen
event_inschrijving_dagen
shifts
shift_inschrijvingen
shift_types
groepen
leden_groepen
```

Do not casually rename Dutch database concepts to English tables without an explicit migration decision.

Application class names may remain English where that is the established architecture.

---

# 15. Legacy shift structures

The new shift implementation must not regress to the old schema.

Do not reintroduce active use of:

```text
event_shifts
shift_toewijzingen
```

Older/legacy copies may exist for migration verification, for example:

```text
event_shifts_legacy
shift_inschrijvingen_legacy
```

Treat these as historical backup data only.

Active shift code must use the current definitive shift tables.

---

# 16. Event module contract

Event management is considered an established module.

Do not redesign it as part of an unrelated shift task.

Relevant concepts include:

```text
evenementen
event_inschrijvingen
```

Events have lifecycle/status behavior already implemented.

Shift logic must integrate with event logic rather than duplicate it.

A shift belongs to an event.

An administrator can mark an event as using member groups for compensation.
When disabled, worked shifts are compensated individually. When enabled,
members with a group are reported under that single group and members without
a group remain in a separate individual section.

## Event creation and publication

An administrator creates and maintains events.

During event creation or editing, an administrator may immediately add shifts
for that event. This is one transactional application workflow coordinated by
the current event and shift services; do not introduce a second shift concept
inside the event module.

Members may only see and register for events that the current event lifecycle
exposes to them. A member registration starts as:

```text
wachtend
```

An administrator decides whether an event registration becomes:

```text
bevestigd
reserve
geweigerd
```

For a multi-day event, the member may select one or more separate event dates.
Selecting all dates represents availability for the complete event. Persist
the selected dates through the current `event_inschrijving_dagen` relation.

A previously cancelled/withdrawn event registration may be submitted again
through the normal member flow. Reuse/reactivate the existing logical
`(event_id, lid_id)` registration and return it to `wachtend`; do not create a
duplicate row.

Publishing an event queues one personalized notification for every eligible
active member. Queue this only when an event actually transitions from a
non-published status to `gepubliceerd`, including initial creation as a
published event. Do not queue a duplicate merely because an already published
event is edited.

The event transaction records the delivery intent in the mail outbox. SMTP
delivery happens later through the mail worker; temporary transport failure
must not roll back or misreport the already committed event mutation.

## Event-registration cancellation

A member may cancel their own active registration for a future event.

- Without an active shift assignment, the event registration is withdrawn
  immediately.
- With one or more active shift assignments, the cancellation remains pending
  until an administrator verifies it.
- On administrator confirmation, all active shift assignments for that member
  and event are cancelled in the same coherent workflow, while historical rows
  remain available.
- Pending cancellation requests for past events must not be shown as actionable
  dashboard work.

An individual cancellation request that requires administrator verification
must remain visible on the platform/dashboard. Do not send an email to all
administrators for this workflow; the organization has too many administrator
accounts for that to be useful.

Before changing an event in a way that affects shifts:

- inspect related shifts;
- preserve referential integrity;
- respect existing event deletion/restriction logic.

Do not silently cascade-delete shift history.

An event without registration history may be hard-deleted. Empty shifts that
also have no registration history may be deleted with it through the current
service workflow. If the event or any shift contains registration history,
preserve that history and use cancellation instead of destructive deletion.

The later event-cancellation mail flow must notify affected event registrants
and confirmed shift volunteers before the corresponding active registrations
are transitioned. Do not implement destructive cleanup ahead of that workflow.

---

# 17. Shift module — definitive domain contract

Shift management is an established module. Future changes must preserve the
administrative-assignment flow documented below.

## Definitive tables

Use:

```text
shift_types
shifts
shift_inschrijvingen
```

## Shift type

A shift has a shift type/function.

The default function is:

```text
Steward
```

Do not create a second competing `shift_types` concept.

Types may contain current metadata such as:

- name;
- color;
- icon;
- description;
- active state.

Use the actual current schema as source of truth.

## Shift timing

A shift has a concrete start datetime and end datetime.

Night shifts are valid.

Example:

```text
18:30 → 01:00 next day
```

Never compare only `HH:mm` strings and reject valid overnight shifts.

The persisted current representation uses full datetime semantics.

## Capacity

The customer/organizer provides the required number of volunteers for each shift.

Capacity is therefore part of the shift.

Capacity applies to:

```text
bevestigd
```

registrations.

`wachtend` and `reserve` do not consume confirmed capacity.

Never approve beyond capacity.

Approval must remain safe against concurrent changes.

## Compensation

Every shift has a compensation amount. The default is:

```text
€ 30,00
```

The amount remains editable by an administrator. Compensation reporting only
counts registrations that are both `bevestigd` and marked `aanwezig`.

For events using member groups, a member with a group earns the supplement
stored on that event per worked shift on top of the configured shift amount.
The default supplement for a new event is € 10,00. That full amount is payable
to the member's association. Members without a group retain the configured
individual shift amount. Multiple worked shifts on one day are summed for that
day.

The compensation report can be filtered to one group so its printable view can
be shared separately. Its admin-only Excel export follows the same event/group
selection and day columns, adds the decrypted member bank account, and must be
treated as confidential. Never expose this export through a member route or
persist a generated plaintext export in application storage.

## Registration statuses

The definitive shift-registration statuses are:

```text
wachtend
bevestigd
reserve
geweigerd
geannuleerd
```

Do not invent synonyms or a second status system.

## Administrative assignment

Members never register themselves for shifts.

Only an administrator may assign a member to a shift. The member must have a
confirmed, active event registration that covers the calendar date of the
shift. An administrative assignment starts as either:

```text
bevestigd
reserve
```

The existing decision actions for historical/waiting shift registrations may
still transition to `bevestigd`, `reserve`, or `geweigerd`, but no member-facing
route or service method may create a shift registration.

## Event registration prerequisite

An administrator may only select members with a confirmed, active event
registration for that event and date. A pending event cancellation blocks new
shift assignment.

Do not remove that rule accidentally when changing UI flow.

## Duplicate registration

There must be at most one logical member/shift registration row under the current unique-key strategy.

A previously cancelled shift assignment may be reactivated/reused through the
established repository/service workflow rather than inserting an invalid
duplicate.

Do not break re-assignment after cancellation.

## Cancellation initiated by a member

A member cancels event participation, not an individual shift assignment.

If the member already has an active shift assignment, the event cancellation
requires administrator verification as documented in the event module
contract. Do not add a member-facing shift cancellation route.

## Cancellation by administrator

An administrator may cancel an active member registration.

The cancellation must preserve historical information and status.

Do not hard-delete the registration.

## Cancelling a complete shift

Cancelling a shift must also transition its active registrations appropriately.

Do not leave:

```text
wachtend
bevestigd
reserve
```

registrations active on a cancelled shift.

Historical rows must remain available.

## Shift deletion

A shift with registration history should not be destructively deleted.

Prefer cancellation.

Only allow hard deletion in the narrow cases already allowed by the current service/repository rules.

## Presence

Presence is meaningful for confirmed registrations.

Current desired UX:

- administrator can mark confirmed volunteers present/not present;
- presence updates should not require a full page refresh;
- the current page/scroll position should remain stable;
- the server remains authoritative;
- AJAX must retain CSRF and admin authorization;
- non-JavaScript fallback should remain possible where practical.

Do not create a second controller solely for presence if `ShiftController` already owns this responsibility.

---

# 18. Shift architecture

Current shift implementation follows the normal AEFS layers.

Relevant types include or are expected under:

```text
app/Models/Shift.php
app/Models/ShiftType.php
app/Models/ShiftRegistration.php

app/Mappers/ShiftMapper.php
app/Mappers/ShiftTypeMapper.php
app/Mappers/ShiftRegistrationMapper.php

app/Repositories/ShiftRepository.php
app/Repositories/ShiftTypeRepository.php
app/Repositories/ShiftRegistrationRepository.php

app/Services/ShiftService.php

app/Validators/ShiftValidator.php
app/Validators/ShiftRegistrationValidator.php

app/Http/Requests/ShiftRequest.php
app/Http/Requests/ShiftRegistrationRequest.php

app/Controllers/ShiftController.php

routes/shifts.php

app/Views/shifts/
```

Before modifying shift functionality, inspect all directly affected files rather than assuming their API.

Do not recreate removed legacy classes such as overlapping shift-registration models/services.

---

# 19. Transaction and concurrency rules

Use database transactions for workflows that modify multiple related rows or depend on a state check followed by a write.

Examples:

- shift approval with capacity checking;
- cancelling a shift plus active registrations;
- registration state transitions;
- multi-row account/registration workflows.

Where capacity/state can race, use the existing locking approach such as row locking through repository methods before decision writes.

Never implement:

```text
read capacity
then later update
```

without transactional protection when concurrent administrators/users could produce an invalid state.

---

# 20. Members module contract

The members module is considered established.

Important rules:

- ordinary members do not receive `/members` administration access;
- administrators manage member records;
- a normal member only accesses their own profile through the member-facing flow;
- sensitive member data must remain protected;
- existing audit behavior must remain intact.

## National identification number

The member field historically named `rijksregisternummer` is the national
identification-number field. It accepts both Belgian and foreign national
identifiers; do not enforce a Belgian-only format.

The value is sensitive and must be stored using the current `enc:v1:`
encryption format. An authorized administrator must be able to see the
decrypted value in member administration. Ordinary members may only see their
own value through the permitted profile flow.

Never expose plaintext national identifiers in logs, audit payloads, exception
messages, URLs, or repository output. The application `app_key` used by the
current encryption service must remain stable across database migrations and
deployments. The legacy encryption key from the old project must never be
committed to this repository.

Legacy national identifiers were migrated with:

```text
database/migrations/20260812_000003_reencrypt_legacy_member_identifiers.php
```

The historical ciphertext backup is retained in:

```text
leden_identificatie_legacy_backup_20260812
```

Active application code must not read that backup table.

## Member groups

Member groups are optional classifications for mailing selection and later
reporting. Administrators may create groups and assign members to them; members
must not manage their own group membership.

A member may belong to at most one group. Enforce this in both the database and
the administration workflow; compensation reporting must never duplicate one
worked shift across multiple groups.

Use the current tables:

```text
groepen
leden_groepen
```

Do not introduce `groepen_leden` as a competing active relation. Group changes
must remain auditable and must not modify or delete the member records.

Do not redesign the module as part of another feature.

Existing sensitive-data handling such as encryption must not be removed.

---

# 21. Users module contract

The users module is considered established.

A user account is linked to a member.

Roles are managed on the user account.

Current roles:

```text
admin
lid
```

Public registration creates a member/user workflow which requires administrator approval before normal account access.

Do not let an unapproved registration bypass the approval state.

Do not duplicate member identity fields unnecessarily into new unrelated tables.

---

# 22. Public registration and approval flow

Current intended workflow:

```text
public registration
→ member record
→ linked user account
→ pending administrator approval
→ approved account
→ login/access according to role
```

Passwords must never be flashed back to session old-input data.

Never log plaintext passwords.

Never expose password hashes.

---

# 23. Dashboard integration

The dashboard is an established module.

When changing table names or domain semantics, inspect:

```text
DashboardRepository
```

and dashboard views/counts.

Do not assume a migration is complete merely because the domain page works; dashboard queries may still reference old schema.

---

# 24. Repository/schema refactors

When changing a table or column used by an established module:

1. search the whole repository for the old identifier;
2. inspect all repository SQL;
3. inspect dashboard/report counters;
4. inspect migrations/seeders;
5. inspect views if field names are surfaced;
6. update all active references;
7. keep legacy references only in deliberate migration/history files.

A schema migration and application update form one coherent change.

---

# 25. Naming conventions

Follow existing names.

PHP:

- classes: PascalCase;
- methods/properties: camelCase;
- constants: UPPER_SNAKE_CASE;
- namespace follows PSR-4;
- files contain one primary class matching the filename.

Database naming is primarily Dutch snake_case.

Do not rename established database terminology merely for stylistic preference.

Use domain names already present in the current module.

---

# 26. PHP standards

All new/modified PHP code must:

```php
<?php

declare(strict_types=1);
```

where appropriate for application/framework classes.

Requirements:

- PHP 8.4 compatible;
- PSR-12 formatting;
- typed parameters;
- typed return values;
- typed properties;
- `readonly` where appropriate;
- no dead imports;
- no undefined methods;
- no placeholder methods;
- no commented-out alternate implementations;
- no debug `var_dump`, `print_r`, `die`, or `exit`.

Prefer:

- early validation;
- clear domain exceptions;
- small cohesive methods;
- named arguments where they improve clarity;
- enums/constants only when consistent with the current codebase.

Do not over-engineer small modules.

---

# 27. SQL standards

SQL must be explicit and readable.

Use parameter binding.

Never concatenate untrusted values into SQL.

Prefer:

- clear aliases;
- explicit selected columns when practical;
- indexes for actual lookup paths;
- foreign keys reflecting domain relationships;
- unique constraints reflecting invariants.

Be careful with MySQL/MariaDB differences and the actual AEFS deployment environment.

When writing migrations, make assumptions explicit.

---

# 28. Security rules

Never place secrets in source code.

Never commit:

- production passwords;
- SMTP passwords;
- API keys;
- private tokens;
- database credentials if they are meant to stay local;
- `.env` secrets.

Never reproduce a secret found in repository history in output.

Treat user/member data as sensitive.

Use:

- existing authorization;
- CSRF protection;
- output escaping;
- password hashing;
- current encryption utilities for sensitive fields.

Views must escape user-controlled output using the existing view escaping mechanism unless intentionally rendering trusted markup.

---

# 29. Data integrity over convenience

When requirements conflict, prioritize:

1. protected user/member data;
2. referential integrity;
3. audit/history;
4. authorization/security;
5. domain correctness;
6. user experience;
7. implementation convenience.

Do not delete history simply because a UI would become easier.

---

# 30. Existing code is the local source of truth

This `AGENTS.md` documents stable contracts, but exact method names and signatures must be verified against the current repository before coding.

For example, before using a method such as:

```php
$repository->findBySomething()
```

search the actual repository class.

Never hallucinate repository/service methods.

Before calling a framework API:

- inspect the current Core;
- inspect nearby working code.

---

# 31. No duplicate abstractions

Before creating a new class, search for an existing class with the same responsibility.

Do not create duplicates such as:

```text
AuditService
AuditLogService
```

for the same purpose.

Do not create both:

```text
ShiftRegistration
ShiftInschrijving
```

as competing active models.

Do not create both:

```text
ShiftService
ShiftRegistrationService
```

when the current architecture intentionally centralizes the domain in one service.

A new class is justified only when it has a distinct responsibility.

---

# 32. Working with legacy code

Historical files may exist in the repository or migration backups.

Do not treat old code as current solely because it compiles.

Signals of legacy code may include:

- references to removed tables;
- references to non-existing roles;
- obsolete controller APIs;
- duplicate models/services;
- old view directories;
- outdated global helpers.

When legacy and current code conflict:

1. inspect route loading;
2. inspect autoloading;
3. inspect current module references;
4. inspect database schema;
5. retain current active architecture.

Ask only if the ambiguity cannot be resolved from the repository.

---

# 33. Current module status

At the current stage, treat the following as established/working areas unless the task explicitly targets them:

```text
Core framework
View Engine
Dashboard
Members
Authentication
Authorization and roles
Public registration
Registration approval flow
User management
Event management
Event registrations and cancellation verification
Shift management and administrative shift assignment
Member groups
Sensitive member-data migration
Mailings and SMTP delivery queue
Reporting: shift attendance list
Reporting: event shift compensations and group totals
Application settings and shift-function administration
```

Later modules may include:

```text
Payments
Documents
```

Do not prematurely implement future modules during a shift task.

---

# 34. Change scope

Make the smallest coherent change that fully solves the requested problem.

Do not bundle unrelated cleanup into a feature fix.

Example:

If the task is:

```text
presence marking causes a full page refresh
```

inspect the existing presence flow and update that flow.

Do not simultaneously:

- replace the router;
- redesign the shift model;
- rewrite the CSS framework;
- add another controller architecture;
- alter member registration.

Refactoring is acceptable only when required to make the requested change correct and maintainable.

---

# 35. Analysis before implementation

For substantial tasks, Codex should first report a concise implementation assessment.

Recommended internal workflow:

```text
1. git status
2. inspect AGENTS.md
3. identify affected module
4. inspect affected files
5. search references
6. inspect relevant schema/migrations
7. determine minimal coherent changes
8. implement
9. lint/test
10. report changed files and result
```

Do not repeatedly explain the plan to the user when implementation can proceed safely.

The user prefers progress through working code over lengthy architectural discussion.

---

# 36. Output expectations for code tasks

When the user asks for code, the normal expectation is:

- complete affected files;
- no snippets;
- no placeholders;
- no pseudo-code;
- copy-paste ready;
- compileable;
- limited explanation.

If Codex edits the repository directly, it should still summarize:

- files changed;
- what changed;
- validation performed.

Do not dump massive unchanged files into chat if Codex has already applied the changes locally unless the user explicitly requests the full code.

When working in ChatGPT-style interactions where files are not directly edited, return complete affected files.

---

# 37. ZIP policy

Do not generate a full project ZIP for small changes.

For a small change, provide/edit only the involved files.

A ZIP is justified only when:

- many new files must be delivered together;
- the user explicitly requests a ZIP;
- packaging itself is part of the task.

Never replace repository-based development with a stream of full project ZIPs.

---

# 38. Git workflow

The repository is Git-managed.

Before modifying code:

```bash
git status
```

Inspect the current branch.

Do not assume the working tree is clean.

Do not overwrite unrelated uncommitted user changes.

## Commits

Do not commit unless the user explicitly asks for a commit.

Do not push unless the user explicitly asks for a push.

Do not reset/discard user changes without explicit permission.

Preferred commit messages follow a concise conventional style, for example:

```text
feat(shifts): add shift registration approval
fix(shifts): update presence without page reload
refactor(events): simplify event repository query
```

## Branches

For significant new work, a focused feature branch is preferred when practical.

Examples:

```text
feature/shift-management
feature/payments
fix/shift-presence
```

Do not create/switch branches unexpectedly if the user asked only for a small local fix.

---

# 39. Required validation after PHP changes

At minimum, lint every changed PHP file:

```bash
php -l path/to/file.php
```

If multiple PHP files changed, lint all of them.

If repository scripts exist for linting/tests, inspect and use them.

Do not claim a file was linted unless the command was actually run.

Do not claim runtime behavior was verified unless it was actually exercised.

---

# 40. Composer/autoload

Do not run `composer dump-autoload` reflexively after every class change.

PSR-4 class additions generally do not require regeneration unless the repository's Composer configuration/classmap setup requires it.

Inspect:

```text
composer.json
```

when autoload behavior is uncertain.

Do not change Composer dependencies for a task that can be solved with the existing stack.

---

# 41. Runtime environment

Primary local development environment is Windows with Laragon.

Code and commands should remain compatible with that environment.

Avoid shell-only assumptions that make normal Windows/Laragon development difficult.

PHP is currently targeted at:

```text
8.4
```

Do not use syntax requiring a newer unsupported PHP version.

---

# 42. Database changes workflow

For a database-changing task:

1. inspect current schema/dump/migrations;
2. determine whether production data exists;
3. identify protected data;
4. write a migration;
5. preserve existing data;
6. update application references;
7. verify counts/relationships;
8. only later remove legacy backups after explicit verification.

For a migration that replaces a populated table, include verification queries where useful.

Do not assume seeders represent production data.

---

# 43. Testing database migrations

For shift migrations or other populated modules, verify:

- row counts before/after;
- orphan rows;
- duplicate keys;
- foreign-key compatibility;
- enum/status mapping;
- date/time conversions;
- special cases such as overnight shifts.

When possible, report the concrete verification result.

---

# 44. AJAX conventions

AJAX is an enhancement, not an authorization boundary.

For an AJAX mutation:

Frontend:

- intercept only the intended form/action;
- submit the existing CSRF token;
- use `fetch`;
- request JSON explicitly;
- disable the clicked control during the request;
- restore state on failure;
- show local success/error feedback;
- avoid losing scroll position.

Backend:

- use the same route authorization;
- validate CSRF;
- call the same service method used by non-AJAX flow;
- return JSON when AJAX is requested;
- preserve redirect behavior otherwise.

Do not create duplicate business logic for AJAX.

---

# 45. Dates and times

Use explicit datetime semantics for domain rules.

All user-facing dates use the Belgian format:

```text
DD/mm/YYYY
```

All user-facing times use 24-hour notation:

```text
HH:mm
```

The application helper `App\Support\BelgianDateTime` is the shared convention
for formatting and normalizing these values. Browser-native date/time controls
may render according to the browser locale, but labels, summaries, tables,
audit output, and server-rendered text must follow the Belgian convention.

Persist dates and full datetimes in the database's current ISO-compatible
formats. Display formatting must not alter persistence semantics.

Do not rely on lexical `HH:mm` comparisons when a shift may cross midnight.

Use immutable date objects where that is already the module convention.

---

# 46. Status transitions

State transitions must be explicit.

For event and shift registrations, valid business transitions depend on current
service rules.

Never allow an arbitrary form value to directly set a protected state.

Examples:

- member event registration -> `wachtend`;
- admin event-registration decision -> `bevestigd`, `reserve`, or `geweigerd`;
- admin shift assignment -> `bevestigd` or `reserve`;
- shift-registration cancellation -> `geannuleerd`;
- event withdrawal -> `uitgeschreven_op` plus the existing cancellation metadata.

Do not let an edit form bypass dedicated transition methods.

---

# 47. Deletion policy

Distinguish between:

- true disposable configuration/data;
- historical business records.

Historical business records should normally be status-transitioned, not hard-deleted.

Examples:

- shift registration history must be retained;
- a populated shift should normally be cancelled rather than deleted.

Never add cascade deletes that silently destroy business history without explicit approval.

---

# 48. Notifications and mail

Mailing is an established module. Do not create a second notification or SMTP
subsystem during unrelated work.

## Definitive persistence

Use:

```text
mailings
mailing_ontvangers
mailing_bijlagen
```

The old `mail_logs` table is obsolete and is not part of the current baseline.

`mailings` stores the campaign/intent and aggregate status.
`mailing_ontvangers` stores one personalized delivery per address, including
attempts, retry state, provider message ID, success timestamp, and the latest
safe error. `mailing_bijlagen` stores attachment metadata and an integrity
hash; files live below the ignored `storage/mail-attachments/` directory.

## Transport and secrets

SMTP transport uses PHPMailer behind:

```text
App\Mail\Transport\MailTransportInterface
```

Gmail SMTP is the preferred deployment configuration. one.com SMTP is a
supported configuration alternative; changing provider must not change domain
or queue code.

Never commit SMTP credentials. The active bootstrap reads them from the
ignored:

```text
config/local/mail.php
```

Use `config/local/mail.example.php` as the non-secret template. Gmail must use
an app password rather than an account password.

During local end-to-end testing, an ignored
`config/local/mail-recipients.php` may contain an explicit recipient
allowlist. When non-empty, both mailing creation and the SMTP transport must
enforce that list. This defense must remain active until unrestricted delivery
is deliberately enabled for production; never remove or bypass it merely to
make a local automatic-flow test easier.

## Queue and delivery

Domain services record mail intent transactionally through `MailService`.
They never contact SMTP directly. Actual delivery runs through:

```text
php bin/process-mail-queue.php
```

The worker sends each recipient separately, processes bounded batches, retries
temporary failures with delay, releases stale locks, records provider results,
and remains safe for concurrent workers through row locking. Keep this outbox
boundary intact.

The local Windows development environment uses:

```text
bin/run-mail-worker.ps1
bin/install-mail-worker-task.ps1
```

The installed local task is named `AEFS v2 Mail Queue (Local)`. It runs every
minute with a maximum of 25 recipients per invocation, prevents overlapping
instances, and only runs while the configured interactive Windows user is
signed in. Empty runs are not written to the worker log.

This local task does not complete the production deployment. The eventual
hosting environment still requires its own cron/scheduler configuration,
appropriate Gmail/provider quota settings, and operational monitoring. Do not
assume background delivery is active in a new environment merely because the
worker code exists.

A committed domain mutation must not be reported as failed solely because SMTP
is temporarily unavailable. Do not send mail from views, repositories, or
ad-hoc controller code.

## Current automatic intents

- event publication to all eligible active members;
- event-registration confirmation to the affected member;
- event-registration reserve decision to the affected member;
- event cancellation to active event registrants and confirmed shift
  volunteers;
- an administrator-triggered personalized overview of all confirmed shifts for
  each assigned member in an event.

The event `planning_verstuurd` timestamp may only be set after every recipient
of that planning mailing has actually been delivered successfully. Merely
queuing the planning is not delivery.

## Current manual audiences

Administrators can compose a plain-text message, optionally add one validated
attachment, and target:

- all eligible active members;
- one or more member groups;
- active registrations of one or more events;
- confirmed/reserve assignments of one or more shifts.

Recipients are deduplicated and `gebruikers.mail_blacklist` is respected.
Never expose recipient lists to other recipients.

## Event-cancellation completion

Changing an event to `geannuleerd` queues one deduplicated notification per
eligible active event registrant or confirmed shift volunteer. The event status
changes immediately, but active event registrations, shifts, and shift
assignments are only transitioned after every notification in that mailing has
been delivered. The worker completes that historical cancellation
transactionally and retries the completion safely when necessary.

A partial or failed cancellation mailing must leave those related records
active until the failed deliveries have been retried successfully. Individual
member cancellation requests remain platform-only administration work and must
not generate an administrator email.

---

# 48A. Application settings

Application settings are an established administrator-only module under:

```text
routes/settings.php
app/Controllers/SettingsController.php
app/Services/SettingsService.php
app/Repositories/SettingsRepository.php
app/Views/settings/
```

Non-secret settings are stored in:

```text
instellingen
```

This includes organisation and mail presentation names, defaults for new shift
compensation/group events, and the default group supplement. A changed default
must not silently rewrite historical shift or event amounts. The group
supplement used by compensation reports is therefore stored on each event as
`evenementen.groepstoeslag_bedrag`.

Administrators also manage the existing `shift_types` through this module.
Deactivate obsolete functions instead of deleting history. The definitive
default function `Steward` must remain present and active.

The settings page may show read-only operational status for mail, worker and
security configuration. It must never display or edit SMTP passwords, database
credentials, the application encryption key, or other secrets. Those remain in
the local/deployment configuration.

---

# 49. Performance

Do not optimize prematurely, but avoid obvious N+1 query patterns in listing pages.

For dashboard/list counts, prefer repository-level aggregate queries.

For large member/registration lists, consider:

- indexed foreign keys;
- status indexes;
- query aggregation;
- pagination when needed.

Do not introduce caching unless there is a measured need or explicit request.

---

# 50. Responsive UX requirements

AEFS is used on varying devices.

Any new/modified UI should be usable on:

- desktop;
- laptop;
- tablet;
- phone.

Avoid fixed-width layouts that only work at one viewport.

Tables should use the project's existing responsive approach or an appropriate card/overflow adaptation.

Actions should remain reachable on small screens.

---

# 51. Accessibility baseline

Maintain basic accessibility:

- labels for form controls;
- semantic buttons for actions;
- links for navigation;
- meaningful headings;
- `aria-live` for asynchronous feedback where appropriate;
- keyboard-accessible controls;
- visible focus behavior from the existing design system.

Do not replace buttons with clickable `<div>` elements.

---

# 52. User language

The application UI is Dutch.

New end-user text should normally be Dutch unless the relevant module deliberately uses another language.

Code identifiers remain consistent with the existing mixture of English class names and Dutch domain/database terminology.

Do not translate established database columns merely for consistency.

---

# 53. Do not fabricate repository state

Never state:

```text
"this file exists"
"this method is available"
"the migration has run"
"tests pass"
"the branch is clean"
```

without verifying it from the current repository/runtime.

If the repository contradicts this document, report the concrete contradiction.

---

# 54. Do not trust historical conversation artifacts over repository state

The repository is the current implementation source of truth.

Historical code fragments, old ZIPs, previous drafts, legacy SQL, and earlier AI suggestions may be obsolete.

When continuing development:

```text
current repository
> current database schema
> AGENTS.md stable contracts
> old code/history
```

for implementation details.

Business rules explicitly captured in this file remain authoritative unless the user changes them.

---

# 55. Definition of done for a normal code change

A change is not done merely because code was generated.

For a normal PHP feature/fix, completion means:

- affected current files were inspected;
- architecture was respected;
- no duplicate abstraction was introduced;
- business rules are preserved;
- authorization remains correct;
- CSRF remains correct for mutations;
- database integrity is preserved;
- changed PHP files pass `php -l`;
- relevant runtime behavior is tested when possible;
- unrelated user changes were not overwritten;
- user receives a concise summary.

---

# 56. Definition of done for shift-management work

For shift work specifically, also verify as relevant:

- shift belongs to a valid event;
- overnight shifts remain valid;
- capacity uses confirmed registrations;
- no overbooking through approval race;
- members cannot create or cancel individual shift registrations;
- administrative assignment requires a confirmed event registration covering
  the shift date;
- existing registration is not duplicated;
- re-assignment after cancellation reuses the logical registration;
- event cancellation with active shift assignments requires admin verification;
- admin can still cancel;
- historical rows remain;
- cancelled shifts do not keep active registrations;
- presence only applies where intended;
- AJAX actions do not bypass CSRF/authorization;
- dashboard/event integrations still use current `shifts` schema.

---

# 57. Preferred Codex behavior for this repository

Codex should behave like a senior maintainer of an existing system, not like a greenfield code generator.

Preferred:

- inspect first;
- use current patterns;
- make focused changes;
- run validation;
- explain only what matters.

Avoid:

- speculative architecture;
- unnecessary abstractions;
- verbose repeated planning;
- broad rewrites;
- unrequested dependencies;
- duplicate classes;
- placeholder implementations;
- silent data-destructive migrations.

---

# 58. First action when opening this repository in a new Codex session

When starting a fresh Codex session, perform this orientation before implementing requested changes:

```text
1. Read AGENTS.md.
2. Run git status.
3. Identify current branch.
4. Inspect composer.json.
5. Inspect the relevant module tree.
6. Inspect the current route file for that module.
7. Inspect related models/repositories/services/controllers/views.
8. Search for legacy duplicate references.
9. Only then change code.
```

For a shift task specifically, inspect at least:

```text
app/Models/Shift.php
app/Models/ShiftRegistration.php
app/Models/ShiftType.php
app/Repositories/ShiftRepository.php
app/Repositories/ShiftRegistrationRepository.php
app/Repositories/ShiftTypeRepository.php
app/Services/ShiftService.php
app/Controllers/ShiftController.php
app/Validators/ShiftValidator.php
app/Validators/ShiftRegistrationValidator.php
app/Http/Requests/ShiftRequest.php
app/Http/Requests/ShiftRegistrationRequest.php
routes/shifts.php
app/Views/shifts/
```

Also search for:

```text
event_shifts
shift_toewijzingen
ShiftPresenceController
ShiftRegistrationService
ShiftInschrijving
eventmanager
```

Any active occurrence must be evaluated carefully because these names may indicate obsolete/incorrect architecture.

---

# 59. Alpha deployment and administrator manual

The current administrator manual is generated from:

```text
bin/generate-admin-manual.py
```

and served from:

```text
public/assets/docs/aefs-v2-adminhandleiding.pdf
```

The admin dashboard links to that PDF. When an administrator workflow changes
materially, update and regenerate the manual and visually verify every page.

Production-specific application, database and mail values belong in ignored
files under:

```text
config/local/
```

Never commit the stable `app_key`, database password or SMTP credentials. The
same `app_key` must accompany every deployment of a database containing
encrypted member data.

The tracked baseline:

```text
database/database.sql
```

is schema-only. It must not contain member records, user records, password
hashes, email addresses, bank-account numbers, encrypted identifiers or other
operational/personal data. A full cutover export belongs only in a protected,
ignored local location such as `build/` and must never be committed or pushed.
Generate a fresh private export at the final cutover moment.

The one.com deployment package is generated with:

```text
bin/build-one-com-package.php
```

It must not contain a database dump, local configuration, logs, sessions or
mail attachments. The production mail worker remains a server-side CLI task;
do not expose an unauthenticated HTTP worker merely because a hosting plan
lacks cron or SSH.

Recipient allowlists are environment-specific safety controls. A local or
alpha allowlist must never be assumed to be the definitive production audience.

---

# 60. Final rule

When uncertain, inspect more current code before adding more code.

The objective is not to produce the most code.

The objective is to keep AEFS v2 coherent, secure, data-safe, maintainable, and aligned with its existing custom architecture.
