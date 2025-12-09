# Upgrade Guide

## 4.x to 5.x

Upgrade using Composer:

```bash
composer config minimum-stability
composer require cloudcreativity/ddd-modules:^5.0
```

### Identifiers

A new `any()` method has been added to the `Identifier` interface. This will only affect your implementation if you have
implemented any custom identifier classes. To solve, add the `IsIdentifier` trait to your class.

### Other Changes

Although this release contains other breaking changes, most implementations can upgrade without making any changes. This
is because the majority of changes affect classes that are implemented by this package - and it is unlikely you are
implementing these yourself.

Refer to the changelog for a full list of changes.

## 3.x to 4.x

Upgrade using Composer:

```bash
composer require cloudcreativity/ddd-modules:^4.0
```

Although this release contains breaking changes, most implementations can upgrade without making any changes. This is
because the majority of changes affect classes that are implemented by this package - and it is unlikely you are
implementing these yourself.

Refer to the changelog for a full list of changes.

## 2.x to 3.x

Upgrade using Composer:

```bash
composer require cloudcreativity/ddd-modules:^3.0
```

### Messages

The message interfaces have been moved to the toolkit namespace. This is to make it clearer that these are part of the
toolkit, not the application or infrastructure layers. It matches the `Result` and `Error` interfaces that are already
in the `Toolkit\Result` namespace. I.e. now the toolkit contains both the input and output interfaces.

This is a quick upgrade if you do a search and replace for the following:

- `Contracts\Application\Messages` => `Contracts\Toolkit\Messages`

### Command Queuing

Previously the command bus had a `queue()` method on it. This has been removed and replaced with a new `CommandQueuer`
interface. This is documented in the [Commands chapter.](./application/commands.md#command-queuer)

The upgrade is relatively easy. You'll need to expose a new `CommandQueuer` driving port. Then wherever in your
presentation and delivery layer that you need to queue a command, import that port instead of the `CommandBus` port.

The documentation provides guidance on how to set up a command queuer port.

### Other Changes

Refer to the [changelog](https://github.com/cloudcreativity/ddd-modules/blob/develop/CHANGELOG.md) for other changes in
this release. These are unlikely to affect consuming applications.

## 1.x to 2.x

Upgrade using Composer:

```bash
composer require cloudcreativity/ddd-modules:^2.0
```

### Overview

:::info
This upgrade guide does not cover every single change you will need to make, but gives you enough guidance for the
majority of changes.

The docs have been updated to reflect all the changes in this release. If you're unsure how to upgrade something, refer
to the relevant chapter in these guides for examples.

If you get stuck upgrading something, create an issue in Github so we can help.
:::

#### Hexagonal Architecture

While the `1.x` version was good, the main problem it had was the relationship between the application and
infrastructure layers. The layering of domain, infrastructure then application did not quite work - it always felt like
the right relationship was domain, application and then infrastructure as an external concern.

We have solved this problem by switching to _Hexagonal Architecture_.

The domain layer remains the core of your bounded context's implementation. This is wrapped by the application layer,
i.e. the infrastructure layer is no longer between the domain and the application layers.

Instead, the application layer has a clearly defined boundary. This boundary is expressed by _ports_ - interfaces that
define the use cases of the module - and _adapters_ - the implementations of these interfaces. There are two types of
ports:

- **Driving Ports** (aka _primary_ or _input_ ports) - interfaces that define the use cases of the bounded context.
  These are implemented by application services, and are used by adapters in the outside world to initiate interactions
  with the application. For example, an adapter could be a HTTP controller that takes input from a request and passes it
  to the application via a driving port.
- **Driven Ports** (aka _secondary_ or _output_ ports) - interfaces that define the dependencies of the application
  layer. The adapters that implement these interfaces are in the infrastructure layer. For example, a persistence port
  that has an adapter to read and write data to a database.

The _driving ports_ in this package continue to use the CQRS pattern. So they are your command bus and query bus, plus
inbound integration events via an inbound event bus.

The _driven ports_ define the boundary between the application and infrastructure layer. This uses a _dependency
inversion_ principle. The application layer defines the port as an interface, which is then implemented by an adapter
in the infrastructure layer.

#### Interface Changes

The result of implementing a hexagonal infrastructure is that we've moved around interfaces to reflect this new
approach.

Any interface that is a driving or driven port has been moved to the `Contracts\Application\Ports` namespace, which has
sub-namespaces of `Driving` and `Driven`.

As we were making changes to interfaces, we've also dropped the `Interface` suffix, and moved all interfaces into
the `Contracts` namespace.

#### Traits

We've made a similar change to traits. The `Trait` suffix has been dropped. To avoid collisions with interfaces, we've
used an `Is` prefix where required.

For example, `EntityTrait` is now `IsEntity`.

### Command Bus

Command messages must now implement the `Contracts\Application\Messages\Command` interface.

The command dispatcher interface is now `Contracts\Application\Ports\Driving\CommandDispatcher`.

The concrete implementation has been moved from `Bus` to `Application\Bus`. The constructor argument for the middleware
pipe container has been renamed `middleware` for clarity. This will only affect your implementation if you are using
named parameters.

If your command handler classes have middleware, the interface is
now `Contracts\Application\Messages\DispatchThroughMiddleware`. Additionally, any command middleware are now in
the `Application\Bus\Middleware` namespace.

### Query Bus

Query messages must now implement the `Contracts\Application\Messages\Query` interface.

The query dispatcher interface is now `Contracts\Application\Ports\Driving\QueryDispatcher`.

The concrete implementation has been moved from `Bus` to `Application\Bus`. The constructor argument for the middleware
pipe container has been renamed `middleware` for clarity. This will only affect your implementation if you are using
named parameters.

If your query handler classes have middleware, the interface is
now `Contracts\Application\Messages\DispatchThroughMiddleware`. Additionally, any query middleware are now in
the `Application\Bus\Middleware` namespace.

### Event Bus

Integration event messages must now implement the `Contracts\Application\Messages\IntegrationEvent` interface. The two
methods this interface defines are now `getUuid()` and `getOccurredAt()`.

The previous event bus implementation has been split in two. This is due to the new hexagonal architecture. Receiving
inbound events is now a _driving port_, whereas publishing outbound events occurs via a _driven port_.

The new inbound implementation (previously referred to as a _notifier_) is now in the `Application\InboundEventBus`
namespace. The driving port is `Contracts\Application\Ports\Driving\EventDispatcher`.

The new outbound implementation (referred to as a _publisher_) is now in the `Infrastructure\OutboundEventBus`
namespace. The driven port is `Contracts\Application\Ports\Driven\OutboundEvents\EventPublisher`.

:::tip
The best approach to upgrading your event bus is to refer to the
updated [Integration Events Chapter.](./application/integration-events)
:::

### Aggregates & Entities

Aggregates must now implement either the `Contracts\Domain\AggregateRoot` or `Contracts\Domain\Aggregate` interfaces.
Likewise, for entities the interface is now `Contracts\Domain\Entity`. The traits have been renamed as follows:

- `Domain\EntityTrait` is now `Domain\IsEntity`
- `Domain\EntityWithNullableIdTrait` is now `Domain\IsEntityWithNullableId`.

The identifier interface is now  `Contracts\Toolkit\Identifiers\Identifier`.

### Domain Events

Domain events must now implement the `Contracts\Domain\Events\DomainEvent` interface. The method this interface defines
is now `getOccurredAt()`.

The domain event dispatcher interface is now `Contracts\Events\DomainEventDispatcher`.

The concrete implementations of domain event dispatchers are now in the `Application\DomainEventDispatching` namespace.
The application layer is the correct namespace for these dispatchers, as the domain event dispatcher interface uses the
_dependency inversion_ principle. I.e. the domain layer defines the interface, but the application layer contains the
concrete implementation. This allows the application layer to define listeners for domain events - which in effect means
domain events _bubble_ to the application layer.

:::tip
Domain event dispatchers were not previously documented. A good way of upgrading is to refer to
the [Domain Events chapter in the Application layer.](./application/domain-events)
:::

### Units of Work

Units of work were not previously documented. If you were using them, the best way to upgrade is to refer to the full
documentation in the [Units of Work chapter.](./application/units-of-work)

This includes examples which shows the new location for the relevant interfaces.

### Queues

Queues were not previously documented. If you were using them, the best way to upgrade is to refer to the full
documentation in the [Queues Chapter.](./infrastructure/queues) Additional documentation about
implementing [Asynchronous Processing patterns](./application/asynchronous-processing) is now also provided in the
linked chapter.

### Results & Errors

The interfaces for results and errors have been moved to the `Contracts\Toolkit\Result` namespace. The concrete
implementations are still in the `Toolkit\Result` namespace. So this change only affects interfaces.
