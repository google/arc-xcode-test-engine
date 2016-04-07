## 2.1.1

* Improved overall test parsing time by approximately 50%.

## 2.1.0

* Added a `pre-build` configuration option.

## 2.0.2

* Error line detection is now more flexible. Any output line with "error:" will be detected and
  bubble up as an error.

## 2.0.1

* Better handling of non-unit-test failures, such as CocoaPods getting out of sync.

## 2.0.0

* Coverage is now enabled and **reported** unless explicitly disabled with --no-coverage.
* xcodebuild or llvm-cov failures now properly throw exceptions rather than silently continuing.

## 1.1.0

* Coverage is now enabled unless explicitly disabled with --no-coverage.

## 1.0.1

* Don't run unit tests if no files were provided to the engine and we're not being asked
  to run all tests.

## 1.0.0

* Initial release.
* Provides `arc unit` and `arc unit --coverage` support for a single xcode project/target
  per-project.
