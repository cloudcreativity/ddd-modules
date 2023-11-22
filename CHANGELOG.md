# Change Log

All notable changes to this project will be documented in this file. This project adheres to
[Semantic Versioning](http://semver.org/) and [this changelog format](http://keepachangelog.com/).

## Unreleased

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
