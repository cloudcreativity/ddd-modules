# Change Log

All notable changes to this project will be documented in this file. This project adheres to
[Semantic Versioning](http://semver.org/) and [this changelog format](http://keepachangelog.com/).

## Unreleased

### Added

- Can now provide an enum as the type of the GUID identifier.

## [3.0.0] - 2025-01-29

### Added

- The `Uuid` class now has a static `tryFrom()` method. This will return `null` if the value provided cannot be cast to
  a UUID identifier.
- New `PsrLogExceptionReporter` provides a default exception reporter implementation that logs the exception as an
  error.
- Updated doc block for `Contracts::assert()` to add PHPStan assertion that the precondition is `true` if the method
  call does not throw.
- The following fake classes are now countable, with the count representing the number of items they have captured:
    - `Testing\FakeDomainEventDispatcher`
    - `Testing\FakeExceptionReporter`
    - `Testing\FakeOutboundEventPublisher`
    - `Testing\FakeQueue`
- New `ContextFactory` interface for converting messages and result objects to log context.
- New `Contextual` interface for converting value objects to log context. This is now extended by the `Identifier`
  interface.
- All middleware that log messages are now injected with the new log context factory class. This allows the conversion
  of messages and result objects to be customised by writing an implementation of this interface. This dependency
  injection is optional, as the package provides its own implementation that is used by default.

### Changed

- **BREAKING** The `ObjectContext` class has been renamed to `ObjectDecorator` and the static `from()` method has been
  removed. Use the new `ContextFactory` implementation instead.
- **BREAKING** The `ResultContext` class has been renamed to `ResultDecorator` and the static `from()` method has been
  removed. Use the new `ContextFactory` implementation instead.

## [3.0.0-rc.2] - 2025-01-18

### Added

- New test classes for driven ports and the domain event dispatcher. These are intended to make setting up unit and
  integration tests easier. They can also be used as fakes while you build your real implementation. The classes are in
  the `Testing` namespace and are:
    - `Testing\FakeDomainEventDispatcher`
    - `Testing\FakeExceptionReporter`
    - `Testing\FakeOutboundEventPublisher`
    - `Testing\FakeQueue`
    - `Testing\FakeUnitOfWork`
- Properties on message classes can now be marked as sensitive so that they are not logged. This is an alternative to
  having to implement the `ContextProvider` interface. Mark a property as sensitive using the
  `CloudCreativity\Modules\Toolkit\Loggable\Sensitive` attribute.

## [3.0.0-rc.1] - 2025-01-12

### Added

- Commands can now be queued by the presentation and delivery layer via the `CommandQueuer` port. Refer to the command
  documentation for details.

### Changed

- **BREAKING** Moved the `Message`, `Command`, `Query`, and `IntegrationEvent` interfaces to the `Toolkit\Messages`
  namespace. This is to make it clearer that these are part of the toolkit, not the application or infrastructure
  layers. It matches the `Result` and `Error` interfaces that are already in the `Toolkit\Result` namespace. I.e.
  now the toolkit contains both the input and output interfaces.

### Removed

- **BREAKING** The `CommandDispatcher` driving port no longer has a `queue()` method. Use the new `CommandQueuer` port
  instead.

## [2.0.0] - 2024-12-07

### Changed

- **BREAKING** Removed the sub-namespaces for ports provided by this package, i.e.:
    - `Contracts\Application\Ports\Driven` all interfaces are no longer in sub-namespaces; and
    - `Contracts\Application\Ports\Driven` also has the same change.
- **BREAKING** Renamed the `InboundEventBus\EventDispatcher` port to `InboundEventDispatcher`.
- **BREAKING** Renamed the `OutboundEventBus\EventPublisher` port to `OutboundEventPublisher`.
- Upgraded to PHPStan v2.

## [2.0.0-rc.3] - 2024-10-13

### Added

- The result class now has a `Result::fail()` static method to create a failed result. This is an alias of the
  existing `Result::failed()` method.
- **BREAKING** The `Entity` interface (and therefore the `Aggregate` interface too) now has a `getIdOrFail()` method on
  it. Although technically breaking, if you are using the `IsEntity` or `IsEntityWithNullableId` traits then this method
  is already implemented.
- New `AggregateRoot` interface so that an aggregate root can be distinguished from a regular aggregate or entity.

### Changed

- Remove deprecation message in PHP 8.4.

## [2.0.0-rc.2] - 2024-07-27

### Added

- The `Uuid` identifier class now has a `getBytes()` method
- Can now get a nil UUID from the `Uuid::nil()` static method.

### Changed

- Made resolution of inner handlers lazy in all buses. In several the handler was immediately resolved, so that the
  handler middleware could be calculated. Buses that support handler middleware now first pipe through the bus
  middleware, then resolve the inner handler, then pipe through the handler middleware. This allows inner handler
  constructor injected dependencies to be lazily resolved after the bus middleware has executed. This is important when
  using the setup and teardown middleware for bootstrapping services that may be injected into the inner handler. Buses
  that now lazily resolve inner handlers are:
    - Command bus
    - Query bus
    - Inbound integration event bus
    - Outbound integration event bus
    - Queue bus

## [2.0.0-rc.1] - 2024-05-07

**Refer to the [Upgrade Guide.](./docs/guide/upgrade.md)**

### Added

- **BREAKING** The command bus interface now has a `queue()` method. Our command dispatcher implementation has been
  updated to accept a queue factory closure as its third constructor argument. This is an optional argument, so this
  change only breaks your implementation if you have manually implemented the command dispatch interface.
- The `FailedResultException` now implements `ContextProvider` to get log context from the exception's
  result object. In Laravel applications, this means the exception context will automatically be logged.
- The `Result` interface has a new `abort()` method. This throws a `FailedResultException` if the result is not
  successful.
- The inbound integration event handler container now accepts an optional factory for a default handler. This can be
  used to swallow inbound events that the bounded context does not need to consume. We have also added
  a `SwallowInboundEvent` handler that can be used in this scenario.

### Changed

- **BREAKING** Package now uses a _hexagonal architecture_ approach, which helps clarify the relationship between the
  application and infrastructure layers. This means a number of interfaces have been moved to
  the `Contracts\Application\Ports` namespace, with them differentiated between driving and driven ports.
- **BREAKING** As a number of interfaces had to be moved to a `Ports` namespace, we've tidied them all up by removing
  the `Interface` suffix and moving them to a `Contracts` namespace.
- **BREAKING** We've also removed the `Trait` suffix from traits. To avoid collisions with interfaces, we've use `Is` a
  prefix where it makes sense. For example, `EntityTrait` has become `IsEntity`.
- **BREAKING** The `DomainEventDispatching` namespace has been moved from `Infrastructure` to `Application`. This was
  needed for the new hexagonal architecture approach, but also makes it a lot clearer that domain events are the way the
  domain layer communicates with the application layer.
- **BREAKING** The event bus implementation has been split into an inbound event bus (in the application layer) and an
  outbound event bus (in the infrastructure layer). With the new hexagonal architecture, this changes was required
  because inbound events are received via a driving port, while outbound events are published via a driven port.
- **BREAKING** Refactored the queue implementation so that commands are queued. The queue implementation was previously
  not documented. There is now complete documentation in
  the [Asynchronous Processing chapter](docs/guide/application/asynchronous-processing.md) Refer to that documentation
  to upgrade your implementation.
- **BREAKING** For clarity, the following classes have had their `pipeline` constructor argument renamed
  to `middleware`. You will need to update the construction of these classes if you are using named arguments:
    - `Application\Bus\CommandDispatcher`
    - `Application\Bus\QueryDispatcher`
    - `Application\InboundEventBus\EventDispatcher`
    - `Application\DomainEventDispatching\Dispatcher`
    - `Application\DomainEventDispatching\DeferredDispatcher`
    - `Application\DomainEventDispatching\UnitOfWorkAwareDispatcher`
- **BREAKING** The command and query validators have been merged into a single class - `Application\Bus\Validator`. This
  is because there was no functional difference between the two, so this tidies up the implementation.
- **BREAKING** Renamed the bus `MessageMiddleware` interface to `BusMiddleware` interface. Also changed the type-hint
  for the message from `Message` to `Command|Query`. This makes this interface clearer about its purpose, as it is
  intended only for use with commands and queries - i.e. not integration event messages.
- **BREAKING** the `ResultContext` and `ObjectContext` helper classes have been moved to the `Toolkit\Loggable`
  namespace.
- **BREAKING** The `Result::value()` method now throws a `FailedResultException` if the result is not successful.
  Previously it threw a `ContractException`.
- **BREAKING** The unit of work implementation has been moved to the `Application\UnitOfWork` namespace. Previously it
  was in `Infrastructure\Persistence`. This reflects the fact that the unit of work manager is an application concern.
  The unit of work interface is now a driven port.

### Removed

- **BREAKING** The pipeline builder factory was no longer required, so the following classes/interfaces have been
  deleted. Although breaking, this is unlikely to affect your implementation as these classes were only used internal
  within our bus and dispatch implementations.
    - `Toolkit\Pipeline\PipelineBuilderFactoryInterface`
    - `Toolkit\Pipeline\PipelineBuilderFactory`
- **BREAKING** Removed the following previously deprecated event bus middleware:
    - `LogOutboundIntegrationEvent` - use `Infrastructure\OutboundEventBus\Middleware\LogOutboundEvent` instead.
    - `LogInboundIntegrationEvent` - use `Application\InboundEventBus\Middleware\LogInboundEvent` instead.
- **BREAKING** Removed the `Infrastructure::assert()` helper. This was not documented so is unlikely to be breaking.

## [1.2.0] - 2024-04-05

### Added

- New integration event middleware:
    - `NotifyInUnitOfWork` for notifiers that need to be executed in a unit of work. Note that the documentation for
      Integration Events incorrectly showed the `ExecuteInUnitOfWork` command middleware being used.
    - `SetupBeforeEvent` for doing setup work before an integration event is published or notified, and optionally
      teardown work after.
    - `TeardownAfterEvent` for doing teardown work after an integration event is published or notified.
    - `LogInboundEvent` for logging that an integration event is being received.
    - `LogOutboundEvent` for logging that an integration event is being published.

### Deprecated

- The following integration event middleware are deprecated and will be removed in 2.0:
    - `LogInboundIntegrationEvent`: use `LogInboundEvent` instead.
    - `LogOutboundIntegrationEvent`: use `LogOutboundEvent` instead.

## [1.1.0] - 2024-03-14

### Added

- Allow an outbound integration event handler to implement a `publish()` method. The `handle()` method is still
  supported, but `publish()` makes more sense to describe what the handler does with the event it has been given.

### Fixed

- Added missing UUID 7 and 8 methods to the UUID factory interface.
- The `Result::error()` method now correctly returns the first error message even if it is not on the first error in the
  list.

## [1.0.0] - 2024-03-09

### Removed

- **BREAKING** The following deprecated interfaces have been removed:
    - `Bus\CommandInterface` use `Toolkit\Messages\CommandInterface` instead.
    - `Bus\QueryInterface` use `Toolkit\Messages\QueryInterface` instead.
    - `Bus\DispatchThroughMiddleware` use `Toolkit\Messages\DispatchThroughMiddleware` instead.
    - `Infrastructure\Log\ContextProviderInterface` use `Toolkit\Loggable\ContextProviderInterface` instead.

## [1.0.0-rc.2] - 2024-03-06

### Added

- New `FailedResultException` for throwing result objects that have not succeeded.

### Changed

- **BREAKING**: The `UnitOfWorkAwareDispatcher` now queues deferred events to be dispatched before the unit of work
  commits. Previously it queued them for after the commit. This changes allows communication between different domain
  entities to occur _within_ the unit of work, which is the correct pattern. For example, if an entity or aggregate root
  needs to be updated as a result of another entity or aggregate dispatching a domain event. It also allows an _outbox_
  pattern to be used for the publishing of integration events. This is a breaking change because it changes the order in
  which events and listeners are executed. Listeners that need to be dispatched after the commit should now implement
  the `DispatchAfterCommit` interface.

### Fixed

- The `ExecuteInUnitOfWork` middleware now correctly prevents the unit of work committing if the inner handler returns a
  failed result. Previously the unit of work would have committed, which was incorrect for a failed result.

## [1.0.0-rc.1] - 2024-02-23

### Added

- New event bus notifier implementation that was previously missing. This completes the event bus implementation.
- New message interfaces (command, query, integration event) added to the toolkit.
- New loggable context provider interface added to the toolkit.
- Module basename now supports namespaces where an application only has a single bounded context.

### Changed

- **BREAKING** Moved the following interfaces to the `Toolkit\Messages` namespace:
    - `MessageInterface`
    - `IntegrationEventInterface`
- **BREAKING** Interfaces that type-hinted `Bus\CommandInterface`, `Bus\QueryInterface` or `Bus\MessageInterface` now
  type-hint the new interfaces in the `Toolkit\Messages` namespace.
- **BREAKING** Moved the `EventBus` implementation from `Infrastructure\EventBus` to `EventBus`. In Deptrac, this
  namespace is now part of the _Application Bus_ layer. Renamed the publisher handler and publisher handler containers
  to integration event handler and container - so that they can be used for both the publisher and notifier
  implementations.
- **BREAKING** Removed the `EventBus\PublishThroughMiddleware` interface. Use the
  `Toolkit\Messages\DispatchThroughMiddleware` interface instead.

### Removed

- **BREAKING** removed the `deptrac-layers.yaml` file, in favour of applications including the classes in their own
  Deptrac configuration.

### Deprecated

- The `Bus\CommandInterface`, `Bus\QueryInterface` and `Bus\DispatchThroughMiddleware` interfaces have been deprecated
  in favour of the new interfaces in the `Toolkit\Messages` namespace.
- The `Infrastructure\Log\ContextProviderInterface` is deprecated in favour of the new
  `Toolkit\Loggable\ContextProviderInterface` interface.

## [0.6.1] - 2024-02-09

### Fixed

- Removed `final` from the `DeferredDispatcher` and `UnitOfWorkAwareDispatcher` classes so that they can be extended.

## [0.6.0] - 2024-02-07

### Added

- New `DeferredDispatcher` class for dispatching domain events when not using a unit of work.
- New UUID factory interface and class, that wraps the `ramsey/uuid` factory to return UUID identifiers.
- GUIDs that wrap UUIDs can now be created via the static `Guid::fromUuid()` method.
- New `SetupBeforeDispatch` and `TearDownAfterDispatch` bus middleware, that can be used either to set up (and
  optionally
  tear down) application state around the dispatching of a message, or to just do tear down work.
- The `EventBus` namespace now has a working implementation for publishing integration events.
- Can now provide a closure to the `ListOfErrorsInterface::first()` method to find the first matching error.
- Added the following methods to the `ListOfErrorsInterface`:
    - `contains()` - determines whether the list contains a matching error.
    - `codes()` - returns an array containing the unique error codes in the list.
- Added an `ErrorInterface::is()` method to determine whether an error matches a given code.

### Changed

- **BREAKING** - renamed the domain event `Dispatcher` class to `UnitOfWorkAwareDispatcher`.
- **BREAKING** - removed the `IntegrationEvents` namespace and moved to the `Infrastructure\EventBus` namespace.
- **BREAKING** - the `IntegrationEventInterface` now expects the UUID to be an identifier UUID, not a Ramsey UUID.
- The UUID factory from the `ramsey/uuid` package is now used when creating new UUID identifiers.

### Fixed

- The unit of work manager now correctly handles re-attempts so that deferred events are not dispatched multiple times.

## [0.5.0] - 2023-12-02

### Added

- New `LazyListOfIdentifiers` class for lazy iteration over a list of identifiers.
- Log context for a result now includes the value if it is a scalar value (string, integer, float, or boolean).

### Changed

- BREAKING: add the `$stack` property to the `ListTrait` and `KeyedSetTrait` traits, and use generics to indicate the
  value they hold. This is breaking because it will cause PHPStan to fail for existing classes the use these traits.
- BREAKING: renamed the `LazyIteratorTrait` to `LazyListTrait` and defined its values using generics.

## [0.4.0] - 2023-11-30

### Added

- Log context for a result now includes the value if it implements `ContextProviderInterface` or `IdentifierInterface`.
- BREAKING: added a `safe` method to the `ResultInterface`, that gives access to the result value without throwing an
  exception if the result is an error.

### Fixed

- Remove `EntityTrait::getId()` nullable return type as it is always set.
- Fix generic return type on `Result::ok()` method.

## [0.3.0] - 2023-11-29

### Changed

- BREAKING: moved the `Bus\Results` namespace to `Toolkit\Result`. As part of this move, the interfaces and classes in
  this namespace no longer implement the log `ContextProviderInterface`, as this is an infrastructure dependency.
  Instead, the new `Infrastructure\Log\ObjectContext` and `Infrastructure\Log\ResultContext` class can be used to create
  context for either a result or an object.
- All constructor arguments for the `Toolkit\Result\Error` object are now optional. This allows named arguments to be
  used when creating an error object.
- The `Toolkit\Result\Error` object can now accept only a code, previously it had to have a message.
- The following interfaces no longer extend the log `ContextProviderInterface`. Instead, classes only need to implement
  that log interface if they need to customise how that class is logged.
    - `Bus\MessageInterface`
    - `Infrastructure\Queue\QueueableInterface`

## [0.2.0] - 2023-11-22

### Added

- The command and query validators now allow rules to return `null` to indicate no errors.
- The following dispatchers now accept pipeline builder factories or pipeline containers into their constructor. This
  simplifies creating them, as in most cases a pipeline container can be provided from a dependency helper.
    - `CommandDispatcher`
    - `QueryDispatcher`
    - `DomainEventDispatching\Dispatcher`
    - `Queue`

### Changed

- BREAKING: changed the `ErrorIterableInterface` to `ListOfErrorsInterface`. Result objects now only accept list of
  errors. The `KeyedSetOfErrors` class can be used to convert a list into a keyed set if needed. This helps simplify
  the error handling logic, which was overly complex by having a generic error iterable interface that could either be
  a list or a keyed set.
- BREAKING: The error interface no longer extends `Stringable`. Use `$error->message()` instead, or compose a string
  from multiple properties of the error object.
- BREAKING: The code on an error object is now type-hinted as a `BackedEnum` or `null` - previously it was `mixed`.
  Error codes should be from a defined list, therefore an enum is the correctly defined type.
- BREAKING: The `PipelineBuilderFactory::cast()` method has been renamed `make()`.

### Removed

- BREAKING: removed the `IterableInterface` as there is no need for a list and a keyed set to inherit from the same
  interface.

## [0.1.0] - 2023-11-18

Initial release.

[3.0.0]: https://github.com/cloudcreativity/ddd-modules/compare/v3.0.0-rc.2...v3.0.0

[3.0.0-rc.2]: https://github.com/cloudcreativity/ddd-modules/compare/v3.0.0-rc.1...v3.0.0-rc.2

[3.0.0-rc.1]: https://github.com/cloudcreativity/ddd-modules/compare/v2.0.0...v3.0.0-rc.1

[2.0.0]: https://github.com/cloudcreativity/ddd-modules/compare/v2.0.0-rc.3...v2.0.0