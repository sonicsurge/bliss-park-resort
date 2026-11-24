# Security Notes

## Scope

Bliss Park Resort is a local demonstration application. It implements security
controls in the PHP application and documents the deployment controls that a
real service still needs.

## Threat model and controls

| Risk | Control |
| --- | --- |
| Unauthorised staff or guest actions | Server-side roles and record ownership checks |
| Forged browser requests | POST-only state changes and per-session CSRF tokens |
| SQL injection | Prepared statements with bound parameters and type validation |
| Cross-site scripting | Context-appropriate HTML escaping through `h()` |
| Account guessing and session fixation | Generic login failures, rate limiting, strict cookies, and session-id regeneration |
| Double booking or invalid booking states | Transaction, room lock, overlap query, and explicit state predicates |
| Configuration exposure | `.env` is ignored and blocked; sensitive paths are denied by Apache and the development router |
| Missing visibility | Structured audit events exclude common password, card, and CSRF fields |

## Boundaries

- Payment confirmation is simulated. No payment card or identity data is collected.
- HTTPS, HSTS, secure cookies, database credential rotation, monitoring, alerts,
  backups, and incident response are deployment responsibilities.
- The project is not a penetration-test report or a security certification.

## Reporting a vulnerability

Do not open an issue containing a real password, token, personal data, or proof
of exploitation against a live system. For this local project, open a private
discussion with the repository owner and include a minimal reproduction and the
affected file or endpoint.
