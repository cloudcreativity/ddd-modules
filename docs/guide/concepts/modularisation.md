# Modularisation & Structure

Modularisation is an approach to splitting up a monolith, particularly one that is considered _legacy_ code, into
smaller, more manageable pieces. By adopting a domain-centric approach, this package provides the tooling needed to
modularise a monolith - and how these modules can be transitioned to a microservices architecture over time.

## Why Modularise?

A common problem that we need to solve as architects and software engineers is how to move away from a monolithic
architecture, to _"something better"_.

For some time, microservices have been seen as the answer. However, stating that the desired architecture is
microservices is easy; the questions it poses are:

1. How to get from a monolithic architecture to a microservices architecture incrementally? Because a full re-write
   is rarely an option.
2. Is introducing significant infrastructure complexity appropriate for the software systems in question? Should you
   avoid this complexity now, but build in a way that provides an easy transition to microservices in the future?

One option to solve these questions is to use a _modular monolith_ architecture. Using this as an intermediary step
between a monolith and a microservices architecture allows for a more controlled and incremental transition.

This package provides a toolset for writing highly encapsulated and loosely coupled modules. These
modules can start their life within a monolith, helping modularise it while providing a clear pathway to
_lifting and shifting_ the module to an independent microservice. Or alternatively, some modules can start their life
immediately as a microservice while using this package to ensure they are implemented in a consistent way to all other
modules.

:::tip
This chapters shows how we choose to structure our modules. There is nothing about the package's implementation that
forces you to use this structure. However, we've described our preferred structure as we believe that it provides a good
starting point for most projects.
:::

## Modular Monolith

In a modular monolith, we implement each module (i.e. bounded context or subdomain) as a separate namespace within a
`Modules` namespace. We follow a consistent structure for each module. This provides predictability for developers
moving between different modules.

However, it is also designed so that the module can be _lifted and shifted_ from a modular monolith to a microservice
architecture.

The top-level namespace of the `Modules` namespace looks like this:

```
- Modules
    - <ModuleName>
        - Application
        - Domain
        - Infrastructure
        - Consumer
        - Shared
    - <ModuleName>
        - Application
        - Domain
        - Infrastructure
        - Consumer
        - Shared
    - <etc>
```

As a top-level summary, the namespaces in each module are:

1. **Domain** - the domain business logic, expressed in aggregates, entities, value objects etc.
2. **Application** - the use cases of the module, along with the driving and driven ports.
3. **Infrastructure** - the adapters that implement the application's driven ports.
4. **Consumer** - the contracts that define the coupling between the module and others, in particular defining
   the data contracts for information exchange.
5. **Shared** - code that is shared between the module and the consumer. This should be limited to
   shared data models - i.e. integration events and read models - and any value objects needed by these models.

:::tip
Note that there is no _presentation layer_ here. Presentation and delivery is _outside_ the `Modules` namespace. This is
because presentation is the outermost layer of the architecture.

For example, in a Laravel application we would be using `App\Modules` as the module namespace. That means everything
outside `App\Modules` is a concern of the presentation and delivery layer.
:::

## Microservices

In a microservice, we would also have a `Modules` namespace. This would contain the one or more subdomains that
the microservice represents.

The structure here is as follows:

```
- Modules
    - <ModuleName>
        - Application
        - Domain
        - Infrastructure
    - <ModuleName>
        - Application
        - Domain
        - Infrastructure
    - <etc>
```

So where have `Shared` and `Consumer` gone?

The consumer namespace is not required by the microservice - as it cannot consume itself! Instead, this is a Composer
package that is installed wherever another module needs to consume this one. For example, this could be in a module in
another microservice, or in your modular monolith. Consumption is either loose via integration events, or direct via a
client interface that internally calls the microservice.

This means we also put the shared namespace in a separate Composer package. This is so that the shared data models can
be required by both the microservice and the consumer package.

For example, the microservice would publish an integration event defined in the shared package. As consumers subscribe
to the integration event, they would also depend on the shared package.

### Transitioning to Microservices

This means there is a clear pathway from a modular monolith to a microservice, by _lifting and shifting_ the code for
the module from the monolith.

If the module has a **shared** namespace, it is moved to a Composer package. This means it can be required by both
the microservice that contains the module and the consumer code.

The **application, domain and infrastructure namespaces** would be _lifted and shifted_ to the `Modules` namespace in
the microservice codebase. If there is a shared package, that can be installed into the microservice via Composer.

The **consumer namespace** would be moved to a Composer package. This means any other module (including those split to
other microservices) can require it as needed. This consumer package represents the allowed coupling to the
microservice, and the client defines the interface for accessing the microservice directly. The consumer package would
require the shared package.

## Layers

### Application Namespace

The application namespace can be structured as follows:

```
- Application
    - Ports
        - Driving
            - CommandBus
            - QueryBus
            - InboundEventBus
        - Driven
            - OutboundEvents
            - Queue
            - Persistence
            - ...
    - Bus
        - CommandBus
        - QueryBus
        - InboundEventBus
    - UseCases
        - Commands
        - Queries
        - InboundEvents
    - Internal
        - Commands
        - DomainEvents
            - Listeners
        - ...
```

The namespaces shown here are as follows:

- **Ports** - the driving and driven ports of the application layer expressed as interfaces. The driving ports are
  the interfaces that the application layer uses to interact with the outside world. The driven ports are the interfaces
  that the application layer expects to be implemented by the infrastructure layer.
- **Bus** - contains the implementations of the driving ports. The concrete implementations are the command bus,
  query bus, and inbound event bus. Each bus ensures a message is dispatched to the correct handler.
- **Use Cases** - the implementation of the business logic of the application layer. Use cases are expressed as the
  command and query messages that can enter the application, and the handlers that implement what happens when a
  command, query or inbound integration event is dispatched.
- **Internal** - contains any internal concerns of the application layer, that are not exposed as ports. For example,
  domain event listeners, internal commands for asynchronous processing, etc.

### Domain Namespace

The domain namespace can be structured as follows:

```
- Domain
    - Enums
    - Events
    - ValueObjects
    . Aggregate1
    . Aggregate2
    . Entity1
    . ...
```

We are however less prescriptive about the structure of the domain namespace, as each domain is unique.

For example, the above structure places aggregate roots and entities at the top level. However, you may prefer to group
them by aggregate root - particularly if your domain has a large number of aggregates and entities. That could result in
a structure like this:

 ```
 - Domain
    - <Aggregate1>
        - Enums
        - ValueObjects 
        . AggrateRoot1
        . ContainedEntity1
        . ContainedEntity2
    - <Aggregate2>
        - Enums
        - ValueObjects 
        . AggrateRoot2
        . ContainedEntity1
        . ContainedEntity2
 ```

### Infrastructure Namespace

The infrastructure namespace contains the adapters that implement the driven ports of the application layer. We would
structure this according to the structure of the ports in the application namespace, so that it's easy to conceptually
tie the two together.

For example, if our application driven ports looked like this:

```
- Application
    - Ports
        - Driven
            - OutboundEventBus
            - Persistence
            - Queue
```

Then our infrastructure namespace would look like this:

```
- Infrastructure
    - OutboundEventBus
    - Persistence
    - Queue 
```

## Packages

### Shared

The shared namespace is optional, and is only required if the module is consumed by other modules. Where this is the
case, the package contains data models that are shared between the module and its consumers.

There are two types of shared data models:

- **Integration events**: these are shared so that the module can publish them, while consumers can receive and react to
  them. This is loose coupling via a data contract defined on the integration event. This contract is identical at the
  point it is published by the module and when it is received by the consumer.
- **Read models**: these share the current state of a module between the module and its consumers. In the module,
  queries dispatched by the query bus can return read models representing the current state. The same read model might
  need to be shared with a consumer. For example, if the client interface the consumer can call returns the same read
  model - e.g. an HTTP JSON response containing a serialised read model.

Your shared package may also contain enums and value objects, where these help to define and describe the data model on
integration events and/or read models.

One thing to note is that as these data models are shared between the module and the consumer, you cannot make
breaking changes to the data contract without updating every single consumer. In large systems, this can be challenging.
Therefore, it is sensible to version the integration events and read models - allowing you to incrementally update
consumers to the new version.

Therefore the shared namespace could look like this:

```
- Shared
    - Enums
    - IntegrationEvents
        - V1
        - V2
    - ReadModels
        - V1
        - V2
    - ValueObjects
```

### Consumer

The consumer namespace is optional, and is only required if the module is consumed by others. Typically
you should loosely couple modules by using integration events. However, there are scenarios where one module would
need to call another module directly.

:::info
In a microservice architecture, this would be the point where one microservice representing a bounded context or
subdomain calls another microservice representing a separate bounded context or subdomain, e.g. via HTTP or gRPC.
:::

The consumer namespace must not contain any business logic - therefore it must not depend on anything from the domain,
application or infrastructure layers. Instead it contains the interfaces for the direct consumption of the module by
other modules. I.e. it is the _client_ or Software Development Kit (SDK) for the module.


