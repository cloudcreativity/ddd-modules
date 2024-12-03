# Reporting Exceptions

Consistent error reporting is important for understanding the health of your bounded context. When an error occurs, it
is important to capture as much information as possible to help diagnose the issue.

There may be scenarios where your application layer catches exceptions but continues execution. In these cases, it is
important to consistently report the exception for debugging and monitoring purposes. This _exception reporting_ is
implemented via a driven port in the application layer.

This chapter covers that port, as well as explaining why it is best practice to use an exception reporter rather than
just logging the exception.

## Catching Exceptions

There are often scenarios where you need to catch an exception, but continue code execution. Often developers will
manually log the exception directly to a PSR Logger before proceeding with the execution.

This is an example scenario:

```php
try {
    $this->someService->doSomething();
    return true;
} catch (\Throwable $e) {
    $this->logger->error('An error occurred: ', $e->getMessage());
    return false;
}
```

### Problem

The problem with this approach is that it is not consistent. It leaves it open to developers to decide how to log
exceptions each time they catch one. There is unlikely to be much consistency across your code base.

This can make it difficult to monitor and debug issues in production. In the example, the developer has logged the
message but this means we've lost the stack trace. Also, if the exception that was caught had a previous exception, that
has also not been logged.

### Solution

The solution is for developers to use an _exception reporter_ to report exceptions, rather than logging them directly.
This means that all exceptions are reported in the same way, including logging the stack trace and any previous
exceptions.

Our example can be updated to use an exception reporter:

```php
try {
    $this->someService->doSomething();
    return true;
} catch (\Throwable $e) {
    $this->exceptionReporter->report($e);
    return false;
}
```

## Exception Reporter Port

This package provides a driven port in the application layer that allows that layer to report exceptions:

```php
namespace CloudCreativity\Modules\Application\Ports\Driven;

use Throwable;

interface ExceptionReporter
{
    /**
     * Report the exception.
     *
     * @param Throwable $ex
     * @return void
     */
    public function report(Throwable $ex): void;
}
```

Your infrastructure layer should have an adapter that implements this port. This means you can tie your application
layer to any logging service you are using.

### Laravel Example

For example, it is easy to implement this port in Laravel as it already provides an exception reporter. Our
implementation looks like this:

```php
namespace App\Modules\Shared\Infrastructure\Exceptions;

use CloudCreativity\Modules\Contracts\Application\Ports\Driven\ExceptionReporter;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Throwable;

final readonly class ExceptionReporterAdapter implements 
    ExceptionReporter
{
    public function __construct(private ExceptionHandler $handler)
    {
    }

    public function report(Throwable $ex): void
    {
        $this->handler->report($ex);
    }
}
```