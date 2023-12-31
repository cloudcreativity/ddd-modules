# Change Log

All notable changes to this project will be documented in this file. This project adheres to
[Semantic Versioning](http://semver.org/) and [this changelog format](http://keepachangelog.com/).

## Unreleased

### Added

- New `DeferredDispatcher` class for dispatching domain events when not using a unit of work.
- New UUID factory interface and class, that wraps the `ramsey/uuid` factory to return UUID identifiers.
- GUIDs that wrap UUIDs can now be created via the static `Guid::fromUuid()` method.
- New `SetupBeforeDispatch` and `TearDownAfterDispatch` bus middleware, that can be used either to setup (and optionally
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
- The following interfaces no longer extend the log `ContextProviderInterface`. Instead classes only need to implement
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
