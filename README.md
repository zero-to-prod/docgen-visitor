# Zerotoprod\DocgenVisitor

![](art/logo.png)

[![Repo](https://img.shields.io/badge/github-gray?logo=github)](https://github.com/zero-to-prod/docgen-visitor)
[![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/zero-to-prod/docgen-visitor/test.yml?label=test)](https://github.com/zero-to-prod/docgen-visitor/actions)
[![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/zero-to-prod/docgen-visitor/backwards_compatibility.yml?label=backwards_compatibility)](https://github.com/zero-to-prod/docgen-visitor/actions)
[![Packagist Downloads](https://img.shields.io/packagist/dt/zero-to-prod/docgen-visitor?color=blue)](https://packagist.org/packages/zero-to-prod/docgen-visitor/stats)
[![php](https://img.shields.io/packagist/php-v/zero-to-prod/docgen-visitor.svg?color=purple)](https://packagist.org/packages/zero-to-prod/docgen-visitor/stats)
[![Packagist Version](https://img.shields.io/packagist/v/zero-to-prod/docgen-visitor?color=f28d1a)](https://packagist.org/packages/zero-to-prod/docgen-visitor)
[![License](https://img.shields.io/packagist/l/zero-to-prod/docgen-visitor?color=pink)](https://github.com/zero-to-prod/docgen-visitor/blob/main/LICENSE.md)
[![wakatime](https://wakatime.com/badge/github/zero-to-prod/docgen-visitor.svg)](https://wakatime.com/badge/github/zero-to-prod/docgen-visitor)
[![Hits-of-Code](https://hitsofcode.com/github/zero-to-prod/docgen-visitor?branch=main)](https://hitsofcode.com/github/zero-to-prod/docgen-visitor/view?branch=main)

## Contents

- [Introduction](#introduction)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Local Development](./LOCAL_DEVELOPMENT.md)
- [Contributing](#contributing)

## Introduction

A PHP AST visitor for automatically generating or updating docblocks in PHP source code.

## Requirements

- PHP 7.1 or higher.

## Installation

Install `Zerotoprod\DocgenVisitor` via [Composer](https://getcomposer.org/):

```bash
composer require zero-to-prod/docgen-visitor
```

This will add the package to your project’s dependencies and create an autoloader entry for it.

## Usage

Here's how you can use DocgenVisitor to read a PHP file, process its contents, and then write the updated code back to the same file:

```php
<?php

require 'vendor/autoload.php';

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use Zerotoprod\DocgenVisitor\DocgenVisitor;

$comments = ['This is an updated class docblock'];
$changes = []; // This is used to accumulate changes from the DocgenVisitor
$traverser = new NodeTraverser();
$traverser->addVisitor(
    new DocgenVisitor(
        function (Node $node) {
            // Filter comments to specific types
            if ($node instanceof Node\Stmt\Class_) use ($comments) {
                return $comments;
            }
            return [];
        },
        $changes
    )
);

// Apply the visitor to a php file.
$traverser->traverse(
    (new ParserFactory())->createForHostVersion()
        ->parse(file_get_contents('User.php'));
);

$updatedCode = null;
foreach ($changes as $change) {
    // Replace the old docblock text with the new one
    $updatedCode = substr_replace(
        $originalCode,
        $change->text,
        $change->start,
        $change->end - $change->start + 1
    );
}

file_put_contents($filePath, $updatedCode);
```

## Contributing

Contributions, issues, and feature requests are welcome!
Feel free to check the [issues](https://github.com/zero-to-prod/docgen-visitor/issues) page if you want to contribute.

1. Fork the repository.
2. Create a new branch (`git checkout -b feature-branch`).
3. Commit changes (`git commit -m 'Add some feature'`).
4. Push to the branch (`git push origin feature-branch`).
5. Create a new Pull Request.
