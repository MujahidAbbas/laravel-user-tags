# Contributing

Contributions are welcome and will be fully credited. We accept contributions via Pull Requests on [GitHub](https://github.com/mujahidabbas/laravel-user-tags).

## Bug Reports

Before creating a bug report:

1. Search existing [issues](https://github.com/mujahidabbas/laravel-user-tags/issues) to see if the problem has already been reported
2. Make sure you can reproduce the issue

When creating a bug report, please include:

- A clear and descriptive title
- A minimal code example that reproduces the issue
- Your PHP version (`php -v`)
- Your Laravel version (`php artisan --version`)
- Any relevant error messages or stack traces

## Pull Requests

1. Fork the repository and create a feature branch from `main`
2. Follow the [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard
3. Add tests for any new features or bug fixes
4. Update documentation if you're changing behavior
5. Make sure all tests pass before submitting
6. Write a clear pull request description explaining the changes

**Please submit one pull request per feature.** If you have multiple features or fixes, submit them as separate pull requests.

## Code Style

This project follows the [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard.

To automatically format your code using Laravel Pint:

```bash
composer format
```

To run static analysis with PHPStan:

```bash
composer analyse
```

Please ensure your code passes both checks before submitting a pull request.

## Tests

This package uses [Pest](https://pestphp.com/) for testing with [Orchestra Testbench](https://github.com/orchestral/testbench) for Laravel integration testing.

To run the test suite:

```bash
composer test
```

**All pull requests that add new features must include tests.** This helps ensure the package remains stable and reliable.

## Questions?

If you have questions about the contribution process, feel free to open an issue for discussion.
