# Services

A domain service is a component that encapsulates a particular domain concept or business rule - which cannot be
encapsulated on an entity or aggregate root. Services perform business logic that is specific to your domain. They must
always be stateless and should not have any persistence of their own - because persistence is an infrastructure layer
concern.

To use the example given by the [DDD Practitioner's Guide](https://ddd-practitioners.com/home/glossary/domain-service/):

> A banking application might have a domain service that handles the transfer of funds between accounts. This operation
involves multiple domain objects (e.g. accounts) and may also have complex business rules (e.g. validating that the
accounts involved in the transfer belong to the same customer), so it’s not something that can be handled by a single
entity or value object. In this case, a domain service would be the appropriate place to encapsulate this behavior.

DDD Modules does not provide any specific tooling around services, because they are entirely the concern of your
domain. This chapter instead provides some guides on how to _access_ domain services where they are required.

## Command Handlers

In the example banking application given above, a domain service handles the logic for the transfer of funds between
accounts. This is a mutation of the bounded context's state, so would be implemented via a
[command handler](../application/commands) in your application layer.

Here, the domain service can be injected into the handler via constructor dependency injection. An example handler might
look something like this:

```php
namespace App\Modules\BankAccounts\BoundedContext\Application\Commands\TransferFunds;

use App\Modules\BankAccounts\BoundedContext\Domain\Services\TransferFundsServiceInterface;
use App\Modules\BankAccounts\BoundedContext\Infrastructure\Persistence\BankAccountRepositoryInterface;
use CloudCreativity\Modules\Toolkit\Result\Result;

final readonly class TransferFundsHandler implements TransferFundsHandlerInterface
{
    public function __construct(
        private TransferFundsServiceInterface $transferFundsService,
        private BankAccountRepositoryInterface $bankAccountRepository,
    ) {
    }

    public function execute(TransferFundsCommand $command): Result
    {
        $sourceAccount = $this->bankAccountRepository->findOrFail(
            $command->sourceId,
        );

        $destinationAccount = $this->bankAccountRepository->findOrFail(
            $command->destinationId,
        );

        $this->transferFundsService->transfer(
            source: $sourceAccount,
            destination: $destinationAccount,
            amount: $command->amount,
        );

        $this->bankAccountRepository->updateAll(
            $sourceAccount,
            $destinationAccount,
        );

        return Result::ok();
    }
}
```

Notice how the domain service does not hold any state itself. Just like mutations on a specific aggregate root, the
handler needs to persist the changes _after_ executing the business logic.

## Query Handlers

You may also need to use domain services in your [query handlers](../application/queries). For example, you might need
to use a domain service to execute business logic to determine the result of a query.

Using the same bank account application example as above, let's say we wanted to implement a query that returns whether
a customer can transfer funds between two accounts. It would make sense for this to be executed via the same
`TransferFundsServiceInterface` that we used in the command handler.

In this case, we can again use constructor dependency injection, with our query handler looking something like this:

```php
namespace App\Modules\BankAccounts\BoundedContext\Application\Queries\CanTransferFunds;

use App\Modules\BankAccounts\BoundedContext\Domain\Services\TransferFundsServiceInterface;
use App\Modules\BankAccounts\BoundedContext\Infrastructure\Persistence\BankAccountRepositoryInterface;
use App\Modules\BankAccounts\Shared\ReadModels\CannotTransferFundsModel;
use CloudCreativity\Modules\Toolkit\Result\Result;

final readonly class CanTransferFundsHandler implements CanTransferFundsHandlerInterface
{
    public function __construct(
        private TransferFundsServiceInterface $transferFundsService,
        private BankAccountRepositoryInterface $bankAccountRepository,
    ) {
    }

    /**
     * Execute the query.
     *
     * @param CanTransferFundsQuery $query
     * @return Result<array<CannotTransferFundsModel>>
     */
    public function execute(TransferFundsCommand $command): Result
    {
        $sourceAccount = $this->bankAccountRepository->findOrFail(
            $command->sourceId,
        );

        $destinationAccount = $this->bankAccountRepository->findOrFail(
            $command->destinationId,
        );

        $reasons = $this->transferFundsService->canTransfer(
            source: $sourceAccount,
            destination: $destinationAccount,
            amount: $command->amount,
        );

        $models = [];

        foreach ($reasons as $reason) {
            $models[] = new CannotTransferFundsModel(
                code: $reason->code,
                message: $reason->message,
            );
        }

        return Result::ok($models);
    }
}
```

## Aggregates & Entities

There may be times where you have an aggregate or entity that has to execute complex business logic in one of its
state mutations. Examples could include running complex logic to determine if the mutation is allowed to happen; or
doing a complex calculation to determine the updated values to set as the new state.

In these cases, you may want to split the business logic out into a domain service. This can help to keep your entities
and aggregates focused on their core responsibilities, and keep the business logic separate and reusable.

:::tip
It is also a big help with unit testing. When testing the aggregate, you can mock out the service and just check what
the aggregate does with the _result_ of invoking the service. And then unit test the service independently.
:::

The question this raises is: how do you access the domain service from the aggregate or entity? :thinking:

There are multiple ways to do this, but here is our preferred approach.

We use a static `Services` class in the `Domain` namespace. This has a getter for each service that an aggregate or
entity needs to access. The getter is a static method, and the service is injected via a factory function that is
injected via a setter.

:melting_face:

Ok, that's a bit wordy! This example illustrates what we mean:

```php
final class Services
{
    /**
     * @var Closure(): TransferFundsServiceInterface|null
     */
    private static ?Closure $transferFundsService = null;

    /**
     * Set the transfer funds service factory.
     *
     * @param Closure(): TransferFundsServiceInterface $factory
     * @return void
     */
    public static function setTransferFunds(Closure $factory): void
    {
        self::$transferFundsService = $factory;
    }

    public static function getTransferFunds(): TransferFundsServiceInterface
    {
        assert(
            self::$transferFundsService !== null,
            'Transfer funds service factory has not been set.',
        );

        return (self::$transferFundsService)();
    }

    public static function tearDown(): void
    {
        self::$transferFundsService = null;
    }

    private function __construct()
    {
        // no-op
    }
}
```

:::warning
When using this approach, always make sure you provide a static `tearDown()` method - as shown in the example.
This is used for two purposes.

Firstly, it means that when we set the factories before dispatching commands or queries, we can guarantee that we can
clear them between every command and/or query. This ensures we do not accidentally bleed state between
consecutive commands or queries. We use our `SetupBeforeDispatch` middleware to do this - as described in the
[chapter on commands.](../application/commands)

Secondly, in unit tests it means we can reliably tear down the state between each test. This is important
to prevent state bleeding between tests - which can lead to flaky tests or false positives. There's an example of this
in the [domain events chapter, in the testing section.](./events#testing)
:::
