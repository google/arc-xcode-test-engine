<?php
/*
 Copyright 2016-present Google Inc. All Rights Reserved.

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

 http://www.apache.org/licenses/LICENSE-2.0

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
 */

/**
 * Builds, runs, and interprets xcodebuild unit test & coverage results.
 *
 * Requires xcodebuild.
 */
final class XcodeUnitTestEngine extends ArcanistUnitTestEngine {

  private $xcodebuildBinary = 'xcodebuild';
  private $covBinary = 'xcrun llvm-cov';

  private $projectRoot;
  private $affectedTests;
  private $xcodebuild;
  private $coverage;

  public function getEngineConfigurationName() {
    return 'xcode-test-engine';
  }

  protected function supportsRunAllTests() {
    return true;
  }

  public function shouldEchoTestResults() {
    return false; // i.e. this engine does not output its own results.
  }

  protected function loadEnvironment() {
    $this->projectRoot = $this->getWorkingCopy()->getProjectRoot();
    $config_path = $this->getWorkingCopy()->getProjectPath('.arcconfig');

    # TODO(featherless): Find a better way to configure the unit engine, possibly via .arcunit.
    if (!Filesystem::pathExists($config_path)) {
      throw new ArcanistUsageException(
        pht(
          "Unable to find '%s' file to configure xcode-test engine. Create an ".
          "'%s' file in the root directory of the working copy.",
          '.arcconfig',
          '.arcconfig'));
    }

    $data = Filesystem::readFile($config_path);
    $config = null;
    try {
      $config = phutil_json_decode($data);
    } catch (PhutilJSONParserException $ex) {
      throw new PhutilProxyException(
        pht(
          "Expected '%s' file to be a valid JSON file, but ".
          "failed to decode '%s'.",
          '.arcconfig',
          $config_path),
        $ex);
    }

    if (!array_key_exists('unit.xcode', $config)) {
      throw new ArcanistUsageException(
        pht(
          "Unable to find '%s' key in .arcconfig.",
          'unit.xcode'));
    }

    $this->xcodebuild = $config['unit.xcode']['build'];
    
    if ($this->getEnableCoverage()) {
      $this->xcodebuild["enableCodeCoverage"] = "YES";
      $this->coverage = $config['unit.xcode']['coverage'];
    } else {
      $this->xcodebuild["enableCodeCoverage"] = "NO";
    }
  }

  public function run() {
    $this->loadEnvironment();

    $xcodeargs = array();
    foreach ($this->xcodebuild as $key => $value) {
      $xcodeargs []= "-$key \"$value\"";
    }

    // Build and run unit tests
    $future = new ExecFuture('%C %C test',
      $this->xcodebuildBinary, implode(' ', $xcodeargs));
    list(, $xcbuild_stdout, $xcbuild_stderr) = $future->resolve();

    // Extract coverage information
    $coverage = null;
    if ($this->getEnableCoverage()) {
      // Get the OBJROOT
      $future = new ExecFuture('%C %C -showBuildSettings test',
        $this->xcodebuildBinary, implode(' ', $xcodeargs));
      list(, $settings_stdout, ) = $future->resolve();
      if (!preg_match('/OBJROOT = (.+)/', $settings_stdout, $matches)) {
        throw new Exception('Unable to find OBJROOT configuration.');
      }
      
      $objroot = $matches[1];
      $covroot = $objroot."/CodeCoverage/".$this->xcodebuild['scheme'];
      $profdata = $covroot."/Coverage.profdata";
      // TODO(featherless): Find a better way to identify which Product was built.
      $product = $covroot."/Products/Debug-iphonesimulator/".$this->coverage['product'];

      $future = new ExecFuture('%C show -use-color=false -instr-profile "%C" "%C"',
        $this->covBinary, $profdata, $product);
      list(, $coverage, $coverage_error) = $future->resolve();
    }
    
    // TODO(featherless): If we publicized the parseCoverageResults method on
    // XcodeTestResultParser we could parseTestResults, then call parseCoverageResults,
    // and the logic here would map the coverage results to the test results. This
    // might be a cleaner approach.

    return id(new XcodeTestResultParser())
      ->setEnableCoverage($this->getEnableCoverage())
      ->setCoverageFile($coverage)
      ->setProjectRoot($this->projectRoot)
      ->setStderr($xcbuild_stderr)
      ->parseTestResults(null, $xcbuild_stdout);
  }
}
