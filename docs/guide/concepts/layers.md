# Domain-Centric Layers

This package provides a design pattern for implementing and structuring code in a domain-centric approach. Within each
bounded context it uses clearly defined layers, which are organized by domain, not by technical concerns.

The layers within this architecture are as follows - starting from the inner-most layer:

1. [Domain](#domain-layer)
2. [Application](#application-layer)
3. [Infrastructure](#infrastructure-layer)
4. [Presentation](#presentation-layer)

## Domain Layer

The domain layer contains the domain entities, aggregate roots and business logic. It is the core of the module and
should be entirely encapsulated - i.e. it must not depend on any other layer. This allows you to structure your domain
entities and aggregate roots in a way that cleanly encapsulate business logic, ensuring the logic is easy to reason
about and test.

:::tip
It is common, particularly in monoliths, for entities (or _models_) to be entwined with the database layer, or entirely
coupled via the use of an ORM or Active Record implementation. This is a database-centric approach, and must be avoided
in a
domain-centric approach.

The domain layer should be entirely independent of the database structure. The mapping of these entities to and from a
database structure is an entirely separate concern of your persistence implementation in the infrastructure layer.

Internally within your persistence implementation, you can use an ORM or Active Record pattern if you wish. However,
this must never be exposed outside of the infrastructure layer.
:::

## Application Layer

The application layer contains the use cases of the bounded context. These are the business processes that can be
executed by passing information _into_ the application, and the information that can be read _out_ of the application.

This package embraces Hexagonal Architecture to define the boundary of the application layer. This boundary is expressed
by _ports_ - interfaces that define the use cases of the module - and _adapters_ - the implementations of these
interfaces. There are two types of ports:

- **Driving Ports** (aka _primary_ or _input_ ports) - interfaces that define the use cases of the bounded context.
  These are implemented by application services, and are used by adapters in the outside world to initiate interactions
  with the application. For example, an adapter could be a HTTP controller that takes input from a request and passes it
  to the application via a driving port.
- **Driven Ports** (aka _secondary_ or _output_ ports) - interfaces that define the dependencies of the application
  layer. The adapters that implement these interfaces are in the infrastructure layer. For example, a persistence port
  that has an adapter to read and write data to a database.

:::tip
For a more detailed explanation of Hexagonal Architecture - along with some excellent diagrams - we
recommend [this article.](https://medium.com/ssense-tech/hexagonal-architecture-there-are-always-two-sides-to-every-story-bc0780ed7d9c)
:::

When defining the driving ports in the application layer, we follow the Command Query Responsibility Segregation (CQRS)
pattern. This pattern separates read (query) and write (command) operations, which makes it completely clear what is
happening in the bounded context. It also allows for different models to be used for reading and writing, which can be
optimized for their specific use case.

Additionally, the bounded context can emit integration events that can be consumed by other bounded contexts.
Integration events allow loose coupling between modules and can be used to trigger side-effects in other modules.

This means that there are three types of _messages_ that define the use cases of the domain-centric application:

1. **Commands** - that mutate the state of the domain;
2. **Queries** - that read the state of the domain; and
3. **Integration Events** - that are emitted and consumed by other bounded contexts.

## Infrastructure Layer

This layer contains adapters that implement the driven ports defined in the application layer.

This is an _dependency inversion_ principle. The application defines what it needs from the infrastructure layer, and
the infrastructure layer provides the implementations. This means that the application layer never depends on the
infrastructure layer. Anything it needs to interact with must be defined as a driven port.

Infrastructure adapters will interact with external services or resources. Examples include:

1. **Persistence**, e.g. database, file system, caching mechanism, etc.
2. **Third-party services**, e.g. a payment gateway or email service.
3. **External APIs**, e.g. a REST API or gRPC service - including microservices within your architecture.

Typically the driven ports should be defined in a way that prevents domain layer concepts - primarily aggregate roots
and entities - from leaking into the infrastructure layer. For example, the port can define data transfer objects to
pass information to the adapter, and receive information back.

However, there are some scenarios where passing domain concepts to the infrastructure layer is necessary. For example, a
repository that is responsible for persisting an aggregate root must be given that aggregate root.

## Presentation Layer

The presentation layer is the outer-most layer and encompasses the presentation and delivery of the module. With modern
PHP frameworks, such as Laravel, commands and queries can be dispatched and presented via a range of delivery
mechanisms, including:

- HTTP requests and responses - via controller actions, that return various presentation formats - for example, HTML,
  JSON, etc. :thinking: Yes, that's right - JSON is a _presentation_ format.
- Console commands - taking console input and presenting results via console output.
- Notifications - e.g. presentation via an HTML email, a text message, or a Slack notification.
- Etc.

The presentation and delivery layer must _only_ depend on the application layer - specifically the driving ports that
are provided by that layer. This means it can only:

1. Dispatch commands to alter the state of the domain.
2. Execute queries to read the state of the domain.
3. Notify the application layer of an integration event inbound from another module.

Which you may notice is the three message types described in the application layer above. :tada:

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
