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
        - Api
            - Input
            - Output
        - Application
        - Domain
        - Infrastructure
    - <ModuleName>
        - Api
        - Application
        - Domain
        - Infrastructure
    - <etc>
```

As a top-level summary, the namespaces in each module are:

1. **Domain** - the domain business logic, expressed in aggregates, entities, value objects etc.
2. **Application** - orchestrates coordination of the domain and infrastructure layers, as well as defining how the use
   cases of the module are implemented (e.g. via command and query handlers).
3. **Infrastructure** - the adapters that implement the application's driven ports.
4. **Api** - the public interface for interacting with the module, i.e. by the presentation and delivery layer. This
   holds the _input_ contracts and value objects that can be used externally (including the driving ports) and the _
   output) values that are received as a result of interacting with the module.

:::tip
Note that there is no _presentation layer_ here. Presentation and delivery is _outside_ the `Modules` namespace. This is
because presentation is the outermost layer of the architecture.

For example, in a Laravel application we would be using `App\Modules` as the module namespace. That means everything
outside `App\Modules` is a concern of the presentation and delivery layer.
:::

## Microservices

In a microservice, we would also have a `Modules` namespace. This would contain the one or more subdomains that
the microservice represents. This would use the same structure as described above.

The use of the same structure means there is a clear pathway from a modular monolith to a microservice, by _lifting and
shifting_ the code for the module from the monolith into the microservice. As the module is fully encapsulated and
loosely coupled, this can be done with minimal changes to the code. Typically, the only changes would be to the
infrastructure layer, as the module might need to use different implementations of the driven ports in the microservice
than it did in the monolith.

## Layers

### API Namespace

The API namespace defines the public interface for interacting with the module. This is the point of interaction between
the presentation and delivery layer and the module. It contains the data contracts for interacting with the module, as
well as the driving ports that the presentation and delivery layer can call to interact with the module.

Our structure for this namespace is to put the driving ports in the root of the namespace, then split other objects into
either `Input` or `Output` namespaces. The `Input` namespace contains the data contracts for interacting with the
module, e.g. the command and query messages that can be dispatched to the application layer. The `Output` namespace
contains the data contracts for receiving data from the module, e.g. the read models that can be returned from queries.

For example:

```
- Api
    - Input
        - Enums
        - Values
        FooCommand
        BarCommand
        BazQuery
        BatQuery
    - Output
        - Enums
        - Values
        - Models
    - CommandBus      <- driving port
    - QueryBus        <- driving port
    - InboundEventBus <- driving port
```

:::tip
The `Api` namespace makes it extremely easy to enforce your architectural layers in the presentation and delivery layer.
If you're using a tool like [Deptrac](https://github.com/deptrac/deptrac) you can specify that the presentation and
delivery layer can only consume classes from the API namespace of the module. This ensures that there is no incorrect
use of the application, domain and infrastrucute layers - enforcing encapsulation.
:::

### Application Namespace

The application namespace can be structured as follows:

```
- Application
    - Ports                       <- driven ports
        - OutboundEvents
        - Queue
        - Persistence
        - ...
    - Adapters                    <- driving port adapters
        - CommandBusAdapter
        - QueryBusAdapter
        - InboundEventBusAdapter
    - UseCases
        - Internal              <- internal use cases, e.g. for asynchronous processing
            - ...
        - FooCommandHandler     <- uses cases
        - BarQueryHandler
    - Orchestration             <- orchestration of domain and infrastructure layers
```

The namespaces shown here are as follows:

- **Ports** - the driven ports of the application layer expressed as interfaces. The driven ports are the interfaces
  that the application layer expects to be implemented by the infrastructure layer.
- **Adapter** - contains the implementations of the driving ports. The concrete implementations are the command bus,
  query bus, and inbound event bus. Each bus ensures a message is dispatched to the correct handler.
- **Use Cases** - the implementation of the business logic of the application layer, i.e. how the application layer
  handles inbound commands, queries and integration events. Additionally we use an `Internal` namespace in here for
  organising the use cases that are for internal use by the module only - i.e. asynchronous processing.
- **Orchestration** - any classes required to help coordinate with the domain layer. For example, this is where the
  concrete implementation of the domain event dispatcher will go, along with domain event listeners.

### Domain Namespace

The domain namespace can be structured as you wish. It's your domain, so only you know how best to organise it!

### Infrastructure Namespace

The infrastructure namespace contains the adapters that implement the driven ports of the application layer. We would
structure this according to the structure of the driven ports in the application namespace, so that it's easy to
conceptually tie the two together.

For example, if our application driven ports looked like this:

```
- Application
    - Ports
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
