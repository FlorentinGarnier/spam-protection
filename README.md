# Spam Protection

[![CI](https://github.com/FlorentinGarnier/spam-protection/actions/workflows/ci.yml/badge.svg)](https://github.com/FlorentinGarnier/spam-protection/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/florentingarnier/spam-protection.svg)](https://packagist.org/packages/florentingarnier/spam-protection)
[![Total Downloads](https://img.shields.io/packagist/dt/florentingarnier/spam-protection.svg)](https://packagist.org/packages/florentingarnier/spam-protection)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
![PHP](https://img.shields.io/badge/php-%5E8.2-777bb4.svg)

An invisible CAPTCHA alternative for PHP forms. Visitors never solve a puzzle: the form embeds a **challenge**
that the browser solves in the background, and the **submission** is judged on the server by several
independent layers.

- No third-party service, no cookie, no tracking: nothing to declare under the GDPR.
- Framework-agnostic: plain PHP 8.2 with a single dependency, a [PSR-6](https://www.php-fig.org/psr/psr-6/) cache.
- Bots pay an increasing cost: the more they try, the harder the challenge gets.

| Package | Purpose |
|---------|---------|
| **florentingarnier/spam-protection** (this one) | The protection itself, for any PHP application |
| [florentingarnier/spam-protection-bundle](https://github.com/FlorentinGarnier/spam-protection-bundle) | Symfony integration: form type, JavaScript solver, console command |
| [florentingarnier/sylius-spam-protection-plugin](https://github.com/FlorentinGarnier/sylius-spam-protection-plugin) | Protects the Sylius shop forms |

## How it works

Each submission goes through the following checks, in this order. The first failing check rejects it.

| Layer | Rejection reason | Rejects the submission when |
|-------|------------------|-----------------------------|
| Honeypot | `honeypot_filled` | a field hidden from humans has been filled in |
| Timed token | `invalid_timestamp_token` | the signed token is forged, already used, issued for another form, sent less than 3 seconds after the form was displayed, or more than 1 hour after |
| Proof of work | `invalid_proof_of_work` | the browser did not provide a valid solution, or reused one |
| Rate limit | `rate_limit_exceeded` | the IP address has used up its attempts on this form (20 by default) |
| Content | `unreadable_content` | a free-text field is made of random characters, such as `dTqLzVbKxWmPfRjN` |

Tokens and proof-of-work challenges are **single-use**: a fresh challenge is issued every time the form is
displayed.

### Proof of work

The browser looks for a number `n` such that `SHA-256(challenge|n)` starts with a given count of zero bits. The
required difficulty grows with the attempts of the IP address on the form:

| Previous attempts | Difficulty (default base of 10 bits) | Indicative cost in a browser |
|-------------------|--------------------------------------|------------------------------|
| 0 to 4            | 10 bits                              | imperceptible                |
| 5 to 9            | 14 bits                              | about 1 second               |
| 10 and more       | 18 bits                              | several seconds              |

An accepted submission counts as 1 attempt, a rejected one as 3. The counter is kept per form and per IP
address, and expires one hour after the last attempt.

### IP reputation

Addresses of datacenters, VPNs and Tor exit nodes are not blocked, but challenged harder from the very first
submission:

| Risk level | Origin                              | Extra difficulty | Attempt weight | Accepted submissions per hour |
|------------|-------------------------------------|------------------|----------------|-------------------------------|
| `normal`   | any other address                   | 0 bits           | × 1            | 20                            |
| `hosting`  | datacenter, hosting provider, VPN   | 4 bits           | × 4            | 5                             |
| `tor`      | Tor exit node                       | 8 bits           | × 4            | 5                             |

The extra difficulty and the attempt-based increase share a cap of 8 bits, so a challenge always stays solvable
in a browser (18 bits with the default base).

### Content check

Only words of at least 6 Latin letters are analysed, so references (`RX-450B`) and acronyms pass. A word looks
random when it has more than one lowercase-to-uppercase transition (`iPhone` has one, `dTqLzV` has two) or more
than 5 consecutive consonants. A text is rejected when at least half of its analysed words look random.

## Requirements

- PHP 8.2 or later
- A PSR-6 cache pool shared by all your web servers (Redis, Memcached, database…)
- JavaScript in the visitor's browser, to solve the proof of work

## Installation

```bash
composer require florentingarnier/spam-protection
```

## Usage

> Using Symfony? Install the [bundle](https://github.com/FlorentinGarnier/spam-protection-bundle) instead: it
> does all of this for you.

### 1. Create the service

```php
use FlorentinGarnier\SpamProtection\IpReputation\IpReputation;
use FlorentinGarnier\SpamProtection\IpReputation\IpReputationList;
use FlorentinGarnier\SpamProtection\SpamProtection;

$spamProtection = SpamProtection::create(
    secret: $appSecret,         // long random string, signs the tokens
    cache: $cachePool,          // any PSR-6 pool
    ipReputation: new IpReputation(new IpReputationList(__DIR__.'/var/ip_reputation.php')),
    baseDifficulty: 10,         // optional
    maximumAttemptsPerHour: 20, // optional
);
```

### 2. Embed a challenge in the form

```php
$challenge = $spamProtection->issueChallenge('contact', $clientIp);
```

```html
<div hidden aria-hidden="true">
    <input type="text" name="fax_number" autocomplete="off" tabindex="-1">
    <input type="hidden" name="rendered_at" value="<?= htmlspecialchars($challenge->submissionToken) ?>">
    <input type="hidden" name="proof_challenge" value="<?= htmlspecialchars($challenge->proofOfWorkChallenge) ?>"
           data-difficulty="<?= $challenge->difficulty ?>">
    <input type="hidden" name="proof_solution">
</div>
```

The first argument, the **scope**, identifies the form: tokens and attempt counters are not shared between
forms. Before the form is sent, your JavaScript must fill `proof_solution`. The bundle ships a ready-made
[solver](https://github.com/FlorentinGarnier/spam-protection-bundle/blob/main/assets/spam-protection.js) that
you can adapt.

### 3. Verify the submission

```php
use FlorentinGarnier\SpamProtection\Submission;

$verdict = $spamProtection->verify(new Submission(
    scope: 'contact',
    honeypot: $_POST['fax_number'] ?? '',
    submissionToken: $_POST['rendered_at'] ?? '',
    proofOfWorkChallenge: $_POST['proof_challenge'] ?? '',
    proofOfWorkSolution: $_POST['proof_solution'] ?? '',
    contents: [$_POST['message'] ?? ''],
), $clientIp);

if (!$verdict->isAccepted()) {
    $logger->warning('Spam rejected', [
        'reason' => $verdict->rejectionReason->value,
        'ip_risk' => $verdict->ipRiskLevel->value,
    ]);
    // Display the form again, with a fresh challenge.
}
```

Rejection reasons are stable strings: you can rely on them in logs and dashboards.

### 4. Compile the IP lists

`IpReputation` reads a compiled list of IPv4 ranges. As long as no list exists, every address is considered
`normal`, so the protection never blocks anyone by mistake. Compile the lists from any plain-text CIDR source:

```php
use FlorentinGarnier\SpamProtection\IpReputation\IpRangeSet;

(new IpReputationList(__DIR__.'/var/ip_reputation.php'))->save([
    'hosting' => IpRangeSet::fromCidrs(explode("\n", file_get_contents('https://raw.githubusercontent.com/X4BNet/lists_vpn/main/output/datacenter/ipv4.txt'))),
    'tor' => IpRangeSet::fromCidrs(explode("\n", file_get_contents('https://check.torproject.org/torbulkexitlist'))),
]);
```

The file is replaced atomically and loaded through opcache. Ranges are merged and searched by dichotomy, so
lists of tens of thousands of entries stay cheap. Refresh them daily. The bundle provides a console command for
this.

## Testing your own forms

`FlorentinGarnier\SpamProtection\Testing\SpamProtectionTestHelper` forges backdated tokens and solves the proof
of work, so that your functional tests do not have to wait 3 seconds:

```php
$token = SpamProtectionTestHelper::forgeSubmissionToken($secret, 'contact', time() - 4);
$solution = SpamProtectionTestHelper::solveProofOfWork($challenge->proofOfWorkChallenge, $challenge->difficulty);
```

Use a low `baseDifficulty` (4, for example) in tests to keep them fast.

## Limitations

- **The visitor's real IP address matters.** Behind a reverse proxy or a load balancer, configure your framework
  to trust it. Otherwise all visitors share the proxy's address, and the rate limit applies to everyone at once.
- **IPv4 only** for IP reputation: IPv6 addresses are considered `normal`.
- **Residential proxies** are not in any list. They are still slowed down by the proof of work and rate limited.
- **Visitors without JavaScript** cannot submit a protected form.
- This library stops automated spam. It does not replace input validation, CSRF protection or rate limiting of
  sensitive operations such as login.

## Contributing

Contributions are welcome. Please read [CONTRIBUTING.md](CONTRIBUTING.md). Report security issues privately, as
described in [SECURITY.md](SECURITY.md).

## License

Released under the [MIT License](LICENSE).
