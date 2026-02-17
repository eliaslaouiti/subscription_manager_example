# Subscription management API example

This project is a demonstration of a subscription management API done for an interview. 


[![Conventional Commits](https://img.shields.io/badge/Conventional%20Commits-1.0.0-green.svg)](https://www.conventionalcommits.org/)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-level%206-brightgreen)](https://phpstan.org)
[![PHP](https://img.shields.io/badge/PHP%20-8.5-blue)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony%20-8.0-blue)](https://www.php.net/)


---

## 🧱 Tech Stack

- [PHP 8.5](https://www.php.net/)
- [Symfony 8.0](https://symfony.com/)
- [Doctrine ORM](https://www.doctrine-project.org/)
- [PHPUnit](https://docs.phpunit.de/en/11.5/)
- [PHPStan](https://phpstan.org/) for static analysis
- [GrumPHP](https://github.com/phpro/grumphp) for Git hooks
- [Conventional Commits](https://www.conventionalcommits.org/) for commit format

---

## 📖 API Documentation

API documentation is generated with [NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle) and available in two formats:

- **HTML (Swagger UI)**: `/api/doc`
- **JSON (OpenAPI)**: `/api/doc.json`

---

## 🧑‍💻 Contributing

### ⌨️ Local development

#### Prerequisites
- PHP 8.5+
- Composer
- Symfony CLI

#### Setup
```bash
make install
make db
```

#### Start dev server
```bash
make start
```

### 🧪 Run tests

```bash
make test          # all tests
make test-unit     # unit tests only
make test-app      # application (functional) tests only
```

## 🔍 Static Analysis with PHPStan

This project uses PHPStan for static code analysis.

#### 💻 Running PHPStan Locally

To check for errors before pushing your code:

```shell
composer phpstan
```

🧾 Example output:

``` text
------ -------------------------------------------------------------- 
Line   Service/MyService.php
------ -------------------------------------------------------------- 
42     Call to an undefined method App\Entity\User::getFullname().
------ -------------------------------------------------------------- 

[ERROR] Found 1 error
```

When everything is fine:

```text
[OK] No errors
```

🧠 Bonus:
If you're using PhpStorm, you can integrate PHPStan directly into your IDE for real-time feedback while coding.
[📖 Official JetBrains](https://www.jetbrains.com/help/phpstorm/using-phpstan.html#prerequisites)

#### 🛡️ PHPStan pre-commit check

This project uses [GrumPHP](https://github.com/phpro/grumphp) to run static analysis
with [PHPStan](https://phpstan.org/) automatically on each commit.

✅ **PHPStan runs on every `git commit`** (via GrumPHP's `pre-commit` hook) to help prevent bugs and enforce strict
typing early in development.

**🚨 If the commit is blocked...**
You likely have some PHPStan errors that need to be fixed before committing.  
Check the pre-commit hook message to see what's failling.

**⚠️ Don't bypass the hook**, please do not use `--no-verify` to skip pre-commit checks.

---

## 📦 Commit message format: Conventional Commits

We follow the [Conventional Commits](https://www.conventionalcommits.org/) specification to keep our git history clean
and meaningful.

### ✅ Format

```bash
<type>(<scope>): <short description>
```

- type – what kind of change you're making (see below)
- scope – optional, what part of the code is affected
- description – brief explanation of the change (lowercase)

example:

```
feat(auth): add JWT login endpoint
feat: add JWT login endpoint
fix(api): return correct status code for 404
chore(deps): update PHPStan to latest version
refactor(core): simplify event dispatch logic
```

### 🎯 Allowed types

| Type       | Meaning                                  |
|------------|------------------------------------------|
| `feat`     | A new feature                            |
| `fix`      | A bug fix                                |
| `docs`     | Documentation only changes               |
| `style`    | Code style changes (formatting, etc)     |
| `refactor` | Code change that is not a fix or feature |
| `perf`     | Performance improvements                 |
| `test`     | Adding or updating tests                 |
| `chore`    | Tooling or maintenance tasks             |
| `ci`       | CI/CD-related changes                    |
| `build`    | Changes to build scripts or dependencies |
| `revert`   | Reverting a previous commit              |

## Authors

Elias Cédric Laouiti <eliaslaouiti>
