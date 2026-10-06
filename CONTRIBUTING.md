# Contributing

Thank you for considering a contribution!

## Reporting a bug or suggesting a feature

Open an [issue](https://github.com/FlorentinGarnier/spam-protection/issues) describing what you expected, what happened, and how to reproduce it (PHP
version, package version, and a minimal code sample). For security issues, do **not** open an issue: follow
[SECURITY.md](SECURITY.md).

## Submitting a pull request

1. Fork the repository and create a branch from `main`.
2. Install the dependencies: `composer install`.
3. Write a test that fails without your change, then make it pass: `vendor/bin/phpunit`.
4. Follow the existing code style: [Symfony coding standards](https://symfony.com/doc/current/contributing/code/standards.html),
   `declare(strict_types=1);` and the license header in every PHP file.
5. Describe the change in the `Unreleased` section of [CHANGELOG.md](CHANGELOG.md).
6. Open the pull request, explaining the problem it solves.

Keep pull requests focused: one change per pull request is easier to review. Changes to the rejection reasons or
to the token format are breaking changes, because applications rely on them in their logs and dashboards.
