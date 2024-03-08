# Core Concepts

This section of the guide provides the core conceptual concepts that underpin the design of this package.

## Layers

The [Layers Chapter](./layers) explains how each bounded context - or module - is divided into layers. Each layer has a
specific responsibility and is designed to be as decoupled as possible from the other layers. The layers are:

1. Domain
2. Infrastructure
3. Application
4. Framework (encompassing presentation and delivery).

## Encapsulation

The [Encapsulation Chapter](./encapsulation) explains how the bounded context is encapsulated - i.e. only exposes a
defined contract to the outside world, while hiding the internal implementation details.

The chapter describes how **messages** - commands, queries and integration events - are used to pass information between
the outside world and the bounded context. Additionally it covers coupling between bounded contexts for
context-to-context communication.

## Modularisation

The [Modularisation Chapter](./modularisation) explains how this package's modular approach can be used to split
a monolith down into a modular monolith - in an incremental way. This modularisation can then be used to transition to
a microservices architecture over time.
