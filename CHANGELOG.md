# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html). Until 1.0.0, minor versions may contain breaking
changes.

## [Unreleased]

## [0.2.0] - 2026-10-06

### Security

- Attempts from IPv6 addresses are now counted per /64 network. A bot controlling a /64, as most IPv6
  subscribers do, could change address on every submission and never reach the rate limit. IPv4-mapped IPv6
  addresses are counted as the IPv4 address they carry.
- Tokens and proof-of-work challenges can be protected against simultaneous replay: `SingleUseTokenRegistry` and
  `SpamProtection::create()` accept an optional `TokenLock`. Without it, two requests sent at the same instant
  with the same token could both be accepted.

### Added

- `TokenLock` interface, to plug any locking mechanism shared by the web servers.

## [0.1.2] - 2026-10-06

### Fixed

- Release metadata only, no code change. The `v0.1.1` tag was rewritten after its publication on Packagist,
  which keeps the original commit: `0.1.2` contains the same code and matches its Git tag. Prefer `0.1.2`.

## [0.1.1] - 2026-10-06

### Changed

- The package is available on [Packagist](https://packagist.org/packages/florentingarnier/spam-protection): the installation instructions no longer declare a Git repository.

## [0.1.0] - 2026-10-06

### Added

- Initial release: honeypot, single-use timed tokens, proof of work, per-form rate limiting, IP reputation (datacenters, VPNs, Tor) and gibberish detection, behind the `SpamProtection` entry point.

[Unreleased]: https://github.com/FlorentinGarnier/spam-protection/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/FlorentinGarnier/spam-protection/compare/v0.1.2...v0.2.0
[0.1.2]: https://github.com/FlorentinGarnier/spam-protection/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/FlorentinGarnier/spam-protection/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/FlorentinGarnier/spam-protection/releases/tag/v0.1.0
