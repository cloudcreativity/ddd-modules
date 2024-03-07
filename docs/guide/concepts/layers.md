# Domain-Centric Layers

This package provides a design pattern for implementing and structuring code in a domain-centric approach. It uses clear
boundaries between layers, which are organized by domain, not by technical concerns. This is a common approach in DDD
(Domain-Driven Design) and ensures clean architecture.

The layers, starting from the inner-most domain layer, are:

1. [Domain](#domain-layer)
2. [Infrastructure](#infrastructure-layer)
3. [Application](#application-layer)
4. [Framework](#framework-layer) (encompassing presentation and delivery)

## Domain Layer

The domain layer contains the domain entities, aggregate roots and business logic. It is the core of the module and
should be entirely encapsulated - i.e. it must not depend on any other layer. This allows you to structure your domain
entities and aggregate roots in a way that ensures they cleanly encapsulate business logic, ensuring the logic is easy
to reason about and test.

:::tip
It is common, particularly in monoliths, for entities (or _models_) to be entwined with the database layer, or entirely
coupled via the use of an ORM or Active Record implementation. This is a database-centric approach, and must be avoided in a
domain-centric approach.

The domain layer should be entirely independent of the database structure. The mapping of these entities to and from a
database structure is an entirely separate concern of your persistence implementation in the infrastructure layer.

Internally within the infrastructure layer, you can use an ORM or Active Record pattern if you wish, but this must not
be exposed outside of the persistence implementation.
:::

## Infrastructure Layer

This layer accesses any external services or resources required for the implementation. Examples include:

1. **Persistence**, e.g. database, file system, caching mechanism, etc.
2. **Third-party services**, e.g. a payment gateway or email service.
3. **External APIs**, e.g. a REST API or gRPC service - including microservices within your architecture.

The infrastructure layer can depend on the domain layer, but not vice-versa. For example, your persistence
implementations would need to map a domain entity or aggregate root to and from a database structure.

The infrastructure layer must not depend on any layers _above_ it, i.e. the application and framework layers.

## Application Layer

The application layer contains the use cases of the module. These are the business processes that can be executed and
the information that can be read out of the domain.

In practice, the application layer composes the execution of business logic via the domain layer and how these changes
are persisted and communicated via the infrastructure layer.

This package uses three types of _messages_ that define the use cases of the domain-centric module:

1. **Commands** - that mutate the state of the domain;
2. **Queries** - that read the state of the domain; and
3. **Integration Events** - that are published when a domain event occurs.

:::tip
The use of _commands_ and _queries_ means the application layer follows the Command Query Responsibility Segregation
(CQRS) pattern. This pattern separates read (query) and write (command) operations, which aids clean architecture and
makes the module easy to reason about and test.

Integration events allow loose coupling between modules and can be used to trigger side-effects in other modules.
:::

## Framework Layer

The framework layer is the outer-most layer and encompasses the presentation and delivery of the module. We've
chosen to call this the _framework layer_ rather than the _presentation layer_ because with modern PHP frameworks,
such as Laravel, commands and queries can be executed and presented via a range of delivery mechanisms, including:

- HTTP requests and responses (encompassing HTML, JSON, etc.)
- Console commands
- Queued jobs
- Scheduled (CRON) jobs
- Etc.

The framework layer must only depend on the application layer. Or - in other words - it can only:

1. Dispatch commands to alter the state of the domain.
2. Execute queries to read the state of the domain.
3. Notify the application layer of an integration event inbound from another module.

:::info
This package is framework agnostic, so you can use it with any framework - or even any legacy application that does not
use a framework. However, throughout the documentation we will use Laravel for examples.
:::
