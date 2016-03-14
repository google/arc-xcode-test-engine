# xcode-test-engine for arc

xcode-test-engine is a test engine for use with [Phabricator](phabricator.org)'s `arc` command line tool.

## Features

xcode-test-engine presently works well with projects that have a
single root workspace and a single unit test target.

Supports `arc unit`:

    ~ $ arc unit
       PASS   42ms★  MyTests::Test1
       PASS    3ms★  MyTests::Test2
       PASS    2ms★  MyTests::Test3
       PASS    2ms★  MyTests::Test4
       PASS    1ms★  MyTests::Test5

And `arc unit --coverage`:

    ~ $ arc unit --coverage
    COVERAGE REPORT
          5%     src/Widget.m
         18%     src/SomeDirectory/AnotherWidget.m
         69%     src/SomeDirectory/WidgetBuilder.m
         80%     src/Controller/WidgetViewController.m
         93%     src/Controller/WidgetTableViewController.m

## Installation

### Project-specific

Add this repository as a git submodule.

    git submodule init
    git submodule add <url for this repo>

Your `.arcconfig` should list `xcode-test-engine` in the `load`
configuration:

    {
      "load": [
        "path/to/arc-xcode-test-engine"
      ]
    }

### Global

Clone this repository to the same directory where `arcanist` and
`libphutil` are globally located. Your directory structure will
look like so:

    arcanist/
    libphutil/
    arc-xcode-test-engine/

Your `.arcconfig` should list `arc-xcode-test-engine` in the `load`
configuration (without a path):

    {
      "load": [
        "arc-xcode-test-engine"
      ]
    }

## Usage

Create a `.arcunit` file in the root of your project and add the following content:

    {
      "engines": {
        "xcode": {
          "type": "xcode-test-engine",
          "include": "(\\.(m|h|mm|swift)$)",
          "exclude": "(/Pods/)"
        }
      }
    }

Feel free to change the include/exclude regexes to suit your project's needs.

Now modify your `.arcconfig` file by adding the following configuration:

    {
      "unit.xcode": {
        "build": {
          "workspace": "path/to/Workspace.xcworkspace",
          "scheme": "UnitTestsScheme",
          "configuration": "Debug",
          "destination": "platform=iOS Simulator,name=iPhone 6S"
        },
        "coverage": {
          "product": "SomeFramework.framework/SomeFramework"
        }
      }
    }

### Configuration options

    "unit.xcode": {
      "build": { ... }
    }

Any value provided to "build" will be passed along to xcodebuild as a flag.

    "unit.xcode": {
      "coverage": {
        "product": " ... "
      }
    }

Provide the path to the product for which coverage should be
calculated. If building a library/framework this might be the
framework binary product.

### Viewing coverage results in Phabricator

To view coverage results in Phabricator diff UI you must run

    arc diff --coverage

Coverage will appear for affected source files in Side-by-Side
mode as a colored bar.

## License

Licensed under the Apache 2.0 license. See LICENSE for details.
