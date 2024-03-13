# Change Log

All notable changes to this project will be documented in this file. This project adheres to
[Semantic Versioning](http://semver.org/) and [this changelog format](http://keepachangelog.com/).

## Unreleased

### Added

- Allow an outbound integration event handler to implement a `publish()` method. The `handle()` method is still
  supported, but `publish()` makes more sense to describe what the handler does with the event it has been given.

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
