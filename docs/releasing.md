# Releasing

Ansilume uses a single `VERSION` file as the authoritative source for the current release version. The version follows [Semantic Versioning](https://semver.org/) (`MAJOR.MINOR.PATCH`).

## How it works

| Part | Role |
|---|---|
| `VERSION` | Plain text file containing the current version, e.g. `0.1.0` |
| `config/params.php` | Reads `VERSION` at runtime → `Yii::$app->params['version']` |
| Sidebar footer | Displays `v0.1.0` to logged-in users |
| `bin/release` | Script that bumps the version, commits, tags, and pushes |

## Making a release

Ensure your working tree is clean and you are on the `main` branch:

```bash
git checkout main
git pull
```

Then run:

```bash
./bin/release patch   # 0.1.0 → 0.1.1  (bug fixes)
./bin/release minor   # 0.1.0 → 0.2.0  (new features, backwards-compatible)
./bin/release major   # 0.1.0 → 1.0.0  (breaking changes)
```

The script will:

1. Validate that the working tree has no uncommitted changes
2. Read the current version from `VERSION`
3. Increment the appropriate component
4. Write the new version back to `VERSION`
5. Commit: `chore: release v0.1.1`
6. Create an annotated Git tag: `v0.1.1`
7. Push the commit and the tag to `origin main`

## Choosing the right bump

| Change type | Command |
|---|---|
| Bug fixes, security patches, dependency updates | `patch` |
| New features that do not break existing behaviour | `minor` |
| Incompatible API or schema changes, major rewrites | `major` |

## Accessing the version in code

```php
// Anywhere in PHP:
$version = \Yii::$app->params['version']; // e.g. "0.1.1"
```

If the `VERSION` file is missing (e.g. a fresh clone from a branch that predates versioning), the value falls back to `"dev"`.
