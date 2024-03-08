# Modularisation

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

One option to solve these questions is to use _modular monolith_ architecture. Using this as an intermediary step
between a monolith and a microservices architecture allows for a more controlled and incremental transition.

This package provides a toolset for writing highly encapsulated and loosely coupled bounded contexts as modules. These
modules can start their life within a monolith, helping modularise it while providing a clear pathway to
_lifting and shifting_ the module to an independent microservice. Or alternatively, some modules can start their life
immediately as a microservice while using this package to ensure they are implemented in a consistent way to all other
modules.

## Module Structure

This chapter proposes a specific structure for each module. This structure is designed to provide predictability for
developers moving between different modules. However, it is also designed so that the module can be _lifted and shifted_
from a modular monolith to a microservice architecture.

When writing a module in a modular monolith, the following top-level structure is proposed for the folders and
namespaces of classes:

```
- Modules
  - <ModuleName>
    - BoundedContext
    - Consumer
    - Shared
  - <ModuleName>
    - BoundedContext
    - Consumer
    - Shared
  - <etc>
```

:::tip
Note that there is no _framework layer_ here. The framework would be _outside_ the `Modules` namespace. This is because
the framework is the outermost layer of the architecture, which would consume the module's application interface to
dispatch commands or queries.
:::

As a top-level summary, the three namespaces in each module are:

1. **Bounded Context** - contains the domain-centric business logic, encapsulated in the domain, infrastructure and
   application layers.
2. **Consumer** - contains the contracts that define the coupling between the module and others, in particular defining
   the data contracts for information exchange.
3. **Shared** - contains code that is shared between the bounded context and the consumer. This should be limited to
   shared values such as enums, value objects, integration events etc.

### Bounded Context

The bounded context namespace holds the domain-centric business logic. This will contain the domain, infrastructure
and application layers.

The following is an example structure of the bounded context namespace/folders:

```
- BoundedContext
    - Application
        - Commands
            - ...
        - Queries
            - ...
        - IntegrationEvents
            - ...
    - Domain
        - Enums
        - Entities
        - Events
        - ValueObjects
        - ...
    - Infrastructure
        - DependencyInjection
        - DomainEventDispatching
        - Persistence
        - Queue
        - ...
```

This an indicative structure, which is explained in more detail in the chapters covering the tooling for each layer.

> The bounded context _may_ rely on values from the `Shared` module namespace, but must never use anything from the
`Consumer` namespace.

### Consumer

The consumer namespace is optional, and is only required if the module is consumed by other modules. Typically
you should loosely couple modules by using integration events. However, there are scenarios where one module would
need to call another module directly.

:::info
In a microservice architecture, this would be the point where one microservice representing a bounded context calls
another microservice representing a separate bounded context, e.g. HTTP or gRPC.
:::

The consumer namespace must not contain any business logic - therefore it must not depend on anything from the bounded
context. Instead it contains the interfaces for the direct consumption of the module by other bounded contexts. I.e.
it is the _client_ or Software Development Kit (SDK) for the module.

### Shared

The shared namespace is optional, and is only required if the module needs to share values between its bounded context
and consumers.

**Enums and value objects** can be shared across both. This is because they can be used to define data contracts in both
the bounded context and the consumer. For example, an enum might be used by an entity in the domain layer of the bounded
context. The same enum might also be present in the data structure given to a consumer when it queries the bounded
context via a client interface.

**Read models** can be used to share the current state of a bounded context between the bounded context and the consumer.
In the bounded context, queries dispatched by the query bus can return read models representing the current state.
The same read model might need to be shared with the consumer, if the client interface the consumer can call returns
the same state. E.g. an HTTP JSON response containing a serialised read model.

**Integration events** are another example of something that should sit in the shared namespace. This is so the
bounded context can publish them, while consumers of the module can subscribe to them. The integration event
defines the data contract for the exchange - which should be identical at the point it is sent outbound from the
bounded context (i.e. published) and when it is received inbound (subscribed to) by the consumer.

Therefore, an example structure for the shared namespace is:

```
Shared
    - Enums
    - IntegrationEvents
    - ReadModels
    - ValueObjects
```

:::warning
It is sensible to not "over-share" values via the shared namespace.

For example, when you decide that a new value object is needed by a domain entity, that value object should start its
life in the bounded context namespace.

It should only be moved to the shared namespace at the point you decide that
the value object is also needed on a defined data contract that is exposed to consumers. E.g. if you decide to use
that value object on a published integration event.
:::

## Transition to Microservices

The module structure described above is designed to be _lifted and shifted_ to a microservice architecture. This is
achieved as follows.

:::tip
Alternatively, if the bounded context is starting life in a microservice, this is the structure you can use from the
inception of the code to ensure it is shared between the bounded context and the consumer.
:::

If the module has a **shared** namespace, it must be moved to a Composer package. This means it can be required by both
the bounded context and the consumer code.

The **bounded context namespace** would be _lifted and shifted_ to the microservice codebase. This is because the
microservice is the architectural representation of the bounded context. This means the microservice can execute
the module's encapsulated business logic via the application layer - dispatching either commands or queries, and
publishing integration events. If there is a shared package, that can be installed into the microservice via Composer.

The **consumer namespace** would be moved to a Composer package. This means any other module (including those split to
other microservices) can require it as needed - for example, to call the microservice via a client interface.

This consumer package represents the allowed coupling to the module (which is now in a microservice), and defines the
data contracts for information that is passed to and from consumers. The consumer package would require the shared
package.
