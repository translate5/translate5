<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

namespace MittagQI\Translate5\Test;

use Exception;
use Httpful\Request;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\TestSuite;

/**
 * Fork of the Testomat.io PHPUnit listener, extends __destruct about the capability to link bitbucket pipelines and PRs to test runs
 */
class TestomatBitbucketListener implements TestListener
{
    use TestListenerDefaultImplementation;

    private static ?string $runId = null;

    private string $url;

    private ?string $suiteName = null;

    private ?string $apiKey = null;

    private bool $hasFailed = false;

    private array $testStatCounter = [
        'passed' => 0,
        'failed' => 0,
        'error' => 0,
        'skipped' => 0,
        'warning' => 0,
        'incomplete' => 0,
        'risky' => 0,
    ];

    public function __destruct()
    {
        if (! $this->apiKey) {
            return;
        }

        $this->commentBitBucketPR();

        if (! self::$runId) {
            return;
        }

        $body = [
            'api_key' => $this->apiKey,
            'status_event' => $this->hasFailed ? 'fail' : 'pass',
        ];

        try {
            $url = $this->url . "/api/reporter/" . self::$runId;
            $response = \Httpful\Request::put($url)
                ->body($body)
                ->sendsJson()
                ->expectsJson()
                ->send();
        } catch (\Exception $e) {
            // Handle the error here
        }
    }

    public function __construct()
    {
        error_reporting(E_ALL & ~E_DEPRECATED); // http library incompatible
        $this->url = trim(getenv('TESTOMATIO_URL'));
        if (! $this->url) {
            $this->url = 'https://app.testomat.io';
        }
        $this->apiKey = trim(getenv('TESTOMATIO'));
        if (! $this->apiKey) {
            return;
        }

        if (self::$runId) {
            return;
        }
        $this->createRun();
    }

    public function startTest(Test $test): void
    {
    }

    public function startTestSuite(TestSuite $suite): void
    {
        $this->suiteName = null;
        $pattern = '/\\\\([^\\\\]+)Test::/';
        preg_match($pattern, $suite->getName(), $matches);

        if (isset($matches[1])) {
            $result = $matches[1];
            $this->suiteName = $result;
        }
    }

    public function endTest(Test $test, float $time): void
    {
        // Code to execute after each individual test ends
        if ($test instanceof TestCase) {
            if ($test->hasFailed()) {
                return;
            }
        }
        $status = 'passed';
        $this->testStatCounter['passed']++;
        $this->addTestRun($test, $status, '', $time);
    }

    public function addFailure(Test $test, AssertionFailedError $e, float $time): void
    {
        $this->hasFailed = true;
        $this->testStatCounter['failed']++;
        $this->addTestRun($test, 'failed', $e->getMessage(), $time, $e->getTraceAsString());
    }

    public function addError(Test $test, \Throwable $t, float $time): void
    {
        $this->hasFailed = true;
        $this->testStatCounter['error']++;
        $this->addTestRun($test, 'failed', $t->getMessage(), $time, $t->getTraceAsString());
    }

    public function addWarning(Test $test, \Throwable $e, float $time): void
    {
        // Code to handle test warnings
        $this->testStatCounter['warning']++;
    }

    public function addIncompleteTest(Test $test, \Throwable $t, float $time): void
    {
        // Code to handle incomplete tests
        $this->testStatCounter['incomplete']++;
    }

    public function addRiskyTest(Test $test, \Throwable $t, float $time): void
    {
        // Code to handle risky tests
        $this->testStatCounter['risky']++;
    }

    public function addSkippedTest(Test $test, \Throwable $t, float $time): void
    {
        // Code to handle skipped tests
        $this->testStatCounter['skipped']++;
        $this->addTestRun($test, 'skipped', $t->getMessage(), $time);
    }

    protected function createRun()
    {
        $runId = getenv('runId');
        if ($runId) {
            self::$runId = $runId;

            return;
        }

        $params = [];

        $title = $this->getTitle();
        if ($title !== null) {
            $params['title'] = $title;
        }

        if (getenv('TESTOMATIO_RUNGROUP_TITLE')) {
            $params['group_title'] = trim(getenv('TESTOMATIO_RUNGROUP_TITLE'));
        }

        if (getenv('TESTOMATIO_ENV')) {
            $params['env'] = trim(getenv('TESTOMATIO_ENV'));
        }

        if (getenv('TESTOMATIO_TITLE')) {
            $params['title'] = trim(getenv('TESTOMATIO_TITLE'));
        }

        if (getenv('TESTOMATIO_SHARED_RUN')) {
            $params['shared_run'] = trim(getenv('TESTOMATIO_SHARED_RUN'));
        }

        try {
            $url = $this->url . '/api/reporter?api_key=' . $this->apiKey;
            $req = \Httpful\Request::post($url);
            if (! empty($params)) {
                $req = $req->body($params);
            }
            $response = $req
                ->sendsJson()
                ->expectsJson()
                ->send();
        } catch (\Exception $e) {
            // Handle the error here
        }

        self::$runId = $response->body->uid ?? null;
    }

    public function addTestRun(Test $test, $status, $message, $runTime, $trace = null)
    {
        /** @var $test TestCase */
        if (! $this->apiKey) {
            return;
        }

        if ($test instanceof TestCase) {
            $testTitle = $this->humanize($test->getName(false));
        } else {
            $testTitle = 'Unknown';
        }

        $body = [
            'api_key' => $this->apiKey,
            'status' => $status,
            'message' => $message,
            'run_time' => $runTime * 1000,
            'title' => $testTitle,
            'suite_title' => trim($this->suiteName),
        ];

        if (trim(getenv('TESTOMATIO_CREATE'))) {
            $body['create'] = true;
        }

        if ($trace) {
            $body['stack'] = $trace;
        }

        if ($test instanceof \PHPUnit\Framework\TestCase) {
            $testId = $this->getTestId($test->getGroups());

            if ($testId) {
                $body['test_id'] = $testId;
            }

            $data = $test->getProvidedData();
            if ($data) {
                $values = $data;
                $keys = array_map(fn ($i) => "p$i", array_keys($values));

                $body['example'] = array_combine($keys, $values);
            }
        }

        $runId = self::$runId;

        try {
            $url = $this->url . "/api/reporter/$runId/testrun";
            $response = \Httpful\Request::post($url)
                ->body($body)
                ->sendsJson()
                ->expectsJson()
                ->send();
        } catch (\Exception $e) {
            // Handle the error here
        }
    }

    private function getTestId(array $groups)
    {
        foreach ($groups as $group) {
            if (preg_match('/^T\w{8}/', $group)) {
                return substr($group, 1);
            }
        }

        return null;
    }

    private function humanize($name)
    {
        $name = str_replace('_', ' ', $name);
        $name = preg_replace('/([A-Z]+)([A-Z][a-z])/', '\\1 \\2', $name);
        $name = preg_replace('/([a-z\d])([A-Z])/', '\\1 \\2', $name);
        $name = strtolower($name);

        // remove test word from name
        $name = preg_replace('/^test /', '', $name);

        return ucfirst($name);
    }

    private function getTitle(): ?string
    {
        $commit = getenv('BITBUCKET_COMMIT') ?: '';
        $branch = getenv('BITBUCKET_BRANCH') ?: '';
        $prId = getenv('BITBUCKET_PR_ID') ?: null;
        $repo = getenv('BITBUCKET_REPO') ?: '';
        $buildNumber = getenv('BITBUCKET_BUILD_NUMBER') ?: null;

        if (! $commit && ! $branch && ! $repo) {
            return null;
        }

        $title = $branch . ' (' . substr($commit, 8) . ')';

        if (! $prId) {
            return $title;
        }

        return $title . ' PR #' . $prId;
    }

    private function commentBitBucketPR(): void
    {
        $commit = getenv('BITBUCKET_COMMIT') ?: '';
        $branch = getenv('BITBUCKET_BRANCH') ?: '';
        $prId = getenv('BITBUCKET_PR_ID') ?: null;
        $repo = getenv('BITBUCKET_REPO') ?: '';
        $buildNumber = getenv('BITBUCKET_BUILD_NUMBER') ?: null;

        if (! $commit && ! $branch && ! $repo) {
            return;
        }

        $total = array_sum($this->testStatCounter);
        $passed = $this->testStatCounter['passed'] ?? 0;
        $passRate = $total > 0 ? round(($passed / $total) * 100, 2) : 0.0;
        $runId = self::$runId;

        $token = getenv('BITBUCKET_TOKEN');

        if (! $token) {
            return;
        }

        if (! $prId) {
            $prId = $this->getPullRequestId($repo, $branch, $token, $prId);
            if (! $prId) {
                return;
            }
        }

        $summary = <<<TXT
🧪 PHPUnit Test Summary
=========================
✅ Passed:      {$this->testStatCounter['passed']}\n
❌ Failed:      {$this->testStatCounter['failed']}\n
💥 Errors:      {$this->testStatCounter['error']}\n
⚠️  Warnings:   {$this->testStatCounter['warning']}\n
🚧 Incomplete:  {$this->testStatCounter['incomplete']}\n
🤔 Risky:       {$this->testStatCounter['risky']}\n
⏭️  Skipped:    {$this->testStatCounter['skipped']}\n
=========================\n
📊 Total Tests: {$total}\n
📈 Pass Rate:   {$passRate}%\n
https://app.testomat.io/projects/translate5/runs/{$runId}\n
https://bitbucket.org/mittagqi/translate5/pipelines/results/{$buildNumber}
TXT;

        $body = [
            'content' => [
                'raw' => $summary,
            ],
        ];

        try {
            $url = "https://api.bitbucket.org/2.0/repositories/{$repo}/pullrequests/{$prId}/comments";

            $response = Request::post($url)
                ->body($body)
                ->addHeader('Authorization', 'Bearer ' . $token)
                ->sendsJson()
                ->expectsJson()
                ->send();

            if ($response->code >= 400) {
                error_log("[BitbucketReporter] Error sending data to bitbucket: " . $response->raw_body);
                print_r($response);
            }
        } catch (Exception $e) {
            error_log("[BitbucketReporter] exception on sending git data: " . $e->getMessage());
        }
    }

    private function getPullRequestId(bool|array|string $repo, bool|array|string $branch, string $token, $prId): mixed
    {
        try {
            $url = "https://api.bitbucket.org/2.0/repositories/{$repo}/pullrequests?q=source.branch.name=\"{$branch}\"+AND+state=\"OPEN\"";

            $response = Request::get($url)
                ->addHeader('Authorization', 'Bearer ' . $token)
                ->sendsJson()
                ->expectsJson()
                ->send();

            foreach ($response->body->values ?? [] as $value) {
                $prId = $value->id;

                break;
            }

            if ($response->code >= 400) {
                error_log("[BitbucketReporter] Error sending data to bitbucket: " . $response->raw_body);
            }
        } catch (Exception $e) {
            error_log("[BitbucketReporter] exception on sending git data: " . $e->getMessage());
        }

        return $prId;
    }
}
