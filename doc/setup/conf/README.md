# BADHAT Apache Setup Config

These files are the active Apache setup examples.

- `badhat-vhost.conf` composes the virtual hosts.
- `badhat-common.conf` holds the shared production-shaped baseline.
- `badhat-local.conf` overrides only local-development differences.
- `badhat-stage.conf` overrides only staging differences.
- `badhat-directory.conf` holds the public directory rewrite rules.

Do not use `doc/setup/badhat.conf` for new installs. It is a deprecated
monolithic reference kept only for comparing older project configs.
