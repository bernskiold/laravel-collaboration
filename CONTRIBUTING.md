# Contributing

You can send a change to this package with a pull request. Read these guidelines before you open a pull request.

## Procedure

1. Fork the project.
2. Make a new branch.
3. Write the change and its tests.
4. Commit and push the change.
5. Open a pull request. Describe the change in the pull request.

## Guidelines

- Run `composer format` to apply the code style.
- Make each commit in the pull request one complete step.
- If there are merge conflicts, [rebase](https://git-scm.com/book/en/v2/Git-Branching-Rebasing) your branch.
- This package follows [SemVer](https://semver.org/).

## Setup

Clone your fork. Then install the development dependencies:

```bash
composer install
```

## Tests

Run the tests:

```bash
composer test
```

## Code style

Apply the code style with [Pint](https://laravel.com/docs/pint):

```bash
composer format
```
