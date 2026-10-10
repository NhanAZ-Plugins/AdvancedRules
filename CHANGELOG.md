# Changelog

## 1.1.3

- Handle malformed configuration values without passing mixed types to player or form APIs.
- Stop sending the form after a kick and disconnect players who decline or close it when `KickMode` is enabled.
- Refuse acceptance when the agreement marker cannot be saved.
- Pin the pmforms source as a Git submodule and build a standalone PHAR with DevTools.
- Add acceptance flow checks for agreement, decline, close, retry, and malformed configuration.

## 1.1.2

- Existing API 5 manifest version before this maintenance update.
