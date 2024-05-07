# Core Concepts

This section of the guide provides the core conceptual concepts that underpin the design of this package.

## Bounded Contexts

The [Bounded Contexts Chapter](./bounded-contexts) explains what a bounded context is, and how this relates to the
concept of "modules" in this package.

## Layers

The [Layers Chapter](./layers) explains how each bounded context - or module - is divided into layers. Each layer has a
specific responsibility and is designed to be as decoupled as possible from the other layers. The layers are:

1. Domain
2. Application
3. Infrastructure
4. Presentation

## Encapsulation

The [Encapsulation Chapter](./encapsulation) explains how the bounded context is encapsulated. This means the
application layer exposes a defined contract to the outside world, while hiding the internal implementation of the
domain. It also defines the boundary with the infrastructure layer.

The chapter describes how **messages** - commands, queries and integration events - are used to pass information between
the outside world and the bounded context. It also covers **coupling** between bounded contexts for context-to-context
communication.

## Modularisation

The [Modularisation Chapter](./modularisation) explains how this package's modular approach can be used to split
a monolith down into a modular monolith - in an incremental way. This modularisation can then be used to transition to
a microservices architecture over time.
