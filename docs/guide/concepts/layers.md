# Domain-Centric Layers

This package provides a design pattern for implementing and structuring code in a domain-centric approach. Within each
bounded context it uses clearly defined layers, which are organized by domain, not by technical concerns.

The layers within each bounded context are as follows - starting from the inner-most domain layer:

1. [Domain](#domain-layer)
2. [Infrastructure](#infrastructure-layer)
3. [Application](#application-layer)
4. [Presentation](#presentation-layer)

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

Internally within your persistence implementation, you can use an ORM or Active Record pattern if you wish. However,
this must never be exposed outside of the infrastructure layer.
:::

## Infrastructure Layer

This layer accesses any external services or resources required for the implementation. Examples include:

1. **Persistence**, e.g. database, file system, caching mechanism, etc.
2. **Third-party services**, e.g. a payment gateway or email service.
3. **External APIs**, e.g. a REST API or gRPC service - including microservices within your architecture.

The infrastructure layer can depend on the domain layer, but not vice-versa. For example, your persistence
implementations would need to map a domain entity or aggregate root to and from a database structure.

The infrastructure layer must not depend on any layers _above_ it, i.e. the application and presentation layers.

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

## Presentation Layer

The presentation layer is the outer-most layer and encompasses the presentation and delivery of the module. With modern
PHP frameworks, such as Laravel, commands and queries can be dispatched and presented via a range of delivery
mechanisms, including:

- HTTP requests and responses - via controller actions, that return various presentation formats - for example, HTML,
  JSON, etc. :thinking: Yes, that's right - JSON is a _presentation_ format.
- Console commands - taking console input and presenting results via console output.
- Queued jobs - for dispatching commands asynchronously.
- Etc.

The presentation and delivery layer must _only_ depend on the application layer. Or - in other words - it can only:

1. Dispatch commands to alter the state of the domain.
2. Execute queries to read the state of the domain.
3. Notify the application layer of an integration event inbound from another module.

Which you may notice is what your application layer exposes to the outside world. :tada:

## Frameworks

One final thing to note is - what's the role of a PHP framework in all of this? Which layer does it belong to?
:thinking:

The answer is **PHP frameworks provide both _infrastructure_ and _presentation_ components.**

They can _never_ provide domain concerns, because your domain is unique to the bounded context you're writing.
Additionally, they cannot provide the application layer - because this layer composes the execution of your business
logic, defined in the command, query and integration event messages that are unique to your domain.

Using Laravel as an example, the _infrastructure_ components it provides include:

1. Database access via Eloquent ORM.
2. File system access.
3. Caching mechanisms.
4. Email sending.
5. Queueing and queued job dispatching.
6. Etc.

The _presentation and delivery_ components it provides include:

1. HTTP routing and request handling.
2. HTML generation via Blade templates.
3. JSON resource responses.
4. Console command handling and scheduling.
4. Etc.

:::info
This package is framework agnostic, so you can use it with any framework - or even any legacy application that does not
use a framework. However, throughout the documentation we use Laravel for framework examples.
:::
