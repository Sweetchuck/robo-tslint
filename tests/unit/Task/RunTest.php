<?php

use Cheppers\AssetJar\AssetJar;
use Cheppers\Robo\TsLint\Task\Run as RunTask;
use Codeception\Util\Stub;
use Helper\Dummy\Process as DummyProcess;
use Robo\Robo;

/**
 * Class TaskTsLintRunTest.
 */
// @codingStandardsIgnoreStart
class RunTest extends \Codeception\Test\Unit
{
    // @codingStandardsIgnoreEnd

    use \Cheppers\Robo\TsLint\Task\LoadTasks;
    use \Robo\TaskAccessor;
    use \Robo\Common\BuilderAwareTrait;

    /**
     * @param $name
     *
     * @return \ReflectionMethod
     */
    protected static function getMethod($name)
    {
        $class = new ReflectionClass(RunTask::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @var \League\Container\Container
     */
    protected $container = null;

    // @codingStandardsIgnoreStart
    protected function _before()
    {
        // @codingStandardsIgnoreEnd
        $this->container = new \League\Container\Container();
        Robo::setContainer($this->container);
        Robo::configureContainer($this->container);
    }

    /**
     * @return \League\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    public function testGetSetLintReporters()
    {
        $task = new RunTask([
            'lintReporters' => [
                'aKey' => 'aValue',
            ],
        ]);

        $task
            ->addLintReporter('bKey', 'bValue')
            ->addLintReporter('cKey', 'cValue')
            ->removeLintReporter('bKey');

        $this->assertEquals(
            [
                'aKey' => 'aValue',
                'cKey' => 'cValue',
            ],
            $task->getLintReporters()
        );
    }

    /**
     * @return array
     */
    public function casesBuildCommand()
    {
        return [
            'basic' => [
                'node_modules/.bin/tslint',
                [],
                [],
            ],
            'configFile-empty' => [
                "node_modules/.bin/tslint",
                ['configFile' => ''],
                [],
            ],
            'configFile-string' => [
                "node_modules/.bin/tslint --config 'foo'",
                ['configFile' => 'foo'],
                [],
            ],
            'exclude-string' => [
                "node_modules/.bin/tslint --exclude 'foo'",
                ['exclude' => 'foo'],
                [],
            ],
            'exclude-vector' => [
                "node_modules/.bin/tslint --exclude 'foo' --exclude 'bar' --exclude 'baz'",
                ['exclude' => ['foo', 'bar', 'baz']],
                [],
            ],
            'exclude-assoc' => [
                "node_modules/.bin/tslint --exclude 'a' --exclude 'd'",
                [
                    'exclude' => [
                        'a' => true,
                        'b' => null,
                        'c' => false,
                        'd' => true,
                        'e' => false,
                    ]
                ],
                [],
            ],
            'force-false' => [
                "node_modules/.bin/tslint",
                ['force' => false],
                [],
            ],
            'force-true' => [
                "node_modules/.bin/tslint --force",
                ['force' => true],
                [],
            ],
            'out-empty' => [
                "node_modules/.bin/tslint",
                ['out' => false],
                [],
            ],
            'out-foo' => [
                "node_modules/.bin/tslint --out 'foo'",
                ['out' => 'foo'],
                [],
            ],
            'rulesDir-empty' => [
                "node_modules/.bin/tslint",
                ['rulesDir' => ''],
                [],
            ],
            'rulesDir-foo' => [
                "node_modules/.bin/tslint --rules-dir 'foo'",
                ['rulesDir' => 'foo'],
                [],
            ],
            'formattersDir-empty' => [
                "node_modules/.bin/tslint",
                ['formattersDir' => ''],
                [],
            ],
            'formattersDir-foo' => [
                "node_modules/.bin/tslint --formatters-dir 'foo'",
                ['formattersDir' => 'foo'],
                [],
            ],
            'format-empty' => [
                'node_modules/.bin/tslint',
                ['format' => ''],
                [],
            ],
            'format-foo' => [
                "node_modules/.bin/tslint --format 'foo'",
                ['format' => 'foo'],
                [],
            ],
            'project-empty' => [
                'node_modules/.bin/tslint',
                ['project' => ''],
                [],
            ],
            'project-foo' => [
                "node_modules/.bin/tslint --project 'foo'",
                ['project' => 'foo'],
                [],
            ],
            'typeCheck-false' => [
                "node_modules/.bin/tslint",
                ['typeCheck' => ''],
                [],
            ],
            'typeCheck-true' => [
                "node_modules/.bin/tslint --type-check",
                ['typeCheck' => true],
                [],
            ],
            'paths-empty' => [
                "node_modules/.bin/tslint",
                ['paths' => []],
                [],
            ],
            'paths-vector' => [
                "node_modules/.bin/tslint -- 'foo' 'bar' 'baz'",
                ['paths' => ['foo', 'bar', 'baz']],
                [],
            ],
            'paths-assoc' => [
                "node_modules/.bin/tslint -- 'a' 'd'",
                [
                    'paths' => [
                        'a' => true,
                        'b' => null,
                        'c' => false,
                        'd' => true,
                        'e' => false,
                    ]
                ],
                [],
            ],
            'convertFormatTo-empty' => [
                "node_modules/.bin/tslint",
                ['convertFormatTo' => ''],
                [],
            ],
            'convertFormatTo-foo' => [
                implode(' ', [
                    'node_modules/.bin/tslint',
                    "--formatters-dir 'node_modules/tslint-formatters/lib/tslint/formatters'",
                    '--',
                    "'a'",
                    '|',
                    "node node_modules/.bin/tslint-formatters-convert 'yaml2jsonGroupByFiles'",
                ]),
                ['convertFormatTo' => 'yaml2jsonGroupByFiles'],
                ['a'],
            ],
            'convertFormatTo-foo-with-out' => [
                implode(' ', [
                    'node_modules/.bin/tslint',
                    "--formatters-dir 'node_modules/tslint-formatters/lib/tslint/formatters'",
                    '--',
                    "'a'",
                    '|',
                    "node node_modules/.bin/tslint-formatters-convert 'yaml2jsonGroupByFiles' --out 'b'",
                ]),
                [
                    'out' => 'b',
                    'convertFormatTo' => 'yaml2jsonGroupByFiles',
                ],
                ['a'],
            ],
        ];
    }

    /**
     * @dataProvider casesBuildCommand
     *
     * @param string $expected
     * @param array $options
     * @param array $paths
     */
    public function testBuildCommand($expected, array $options, array $paths)
    {
        $tslint = new RunTask($options, $paths);
        static::assertEquals($expected, $tslint->buildCommand());
    }

    public function testExitCodeConstants()
    {
        static::assertEquals(0, RunTask::EXIT_CODE_OK);
        static::assertEquals(1, RunTask::EXIT_CODE_WARNING);
        static::assertEquals(2, RunTask::EXIT_CODE_ERROR);
        static::assertEquals(3, RunTask::EXIT_CODE_INVALID);
    }

    /**
     * @return array
     */
    public function casesGetTaskExitCode()
    {
        $o = RunTask::EXIT_CODE_OK;
        $w = RunTask::EXIT_CODE_WARNING;
        $e = RunTask::EXIT_CODE_ERROR;
        $u = 5;

        return [
            'never-000' => [$o, 'never', 0, 0, 0],
            'never-001' => [$o, 'never', 0, 0, 1],
            'never-002' => [$o, 'never', 0, 0, 2],
            'never-005' => [$u, 'never', 0, 0, 5],

            'never-010' => [$o, 'never', 0, 1, 0],
            'never-011' => [$o, 'never', 0, 1, 1],
            'never-012' => [$o, 'never', 0, 1, 2],
            'never-015' => [$u, 'never', 0, 1, 5],

            'never-100' => [$o, 'never', 1, 0, 0],
            'never-101' => [$o, 'never', 1, 0, 1],
            'never-102' => [$o, 'never', 1, 0, 2],
            'never-105' => [$u, 'never', 1, 0, 5],

            'never-110' => [$o, 'never', 1, 1, 0],
            'never-111' => [$o, 'never', 1, 1, 1],
            'never-112' => [$o, 'never', 1, 1, 2],
            'never-115' => [$u, 'never', 1, 1, 5],

            'warning-000' => [$o, 'warning', 0, 0, 0],
            'warning-001' => [$o, 'warning', 0, 0, 1],
            'warning-002' => [$o, 'warning', 0, 0, 2],
            'warning-005' => [$u, 'warning', 0, 0, 5],

            'warning-010' => [$w, 'warning', 0, 1, 0],
            'warning-011' => [$w, 'warning', 0, 1, 1],
            'warning-012' => [$w, 'warning', 0, 1, 2],
            'warning-015' => [$u, 'warning', 0, 1, 5],

            'warning-100' => [$e, 'warning', 1, 0, 0],
            'warning-101' => [$e, 'warning', 1, 0, 1],
            'warning-102' => [$e, 'warning', 1, 0, 2],
            'warning-105' => [$u, 'warning', 1, 0, 5],

            'warning-110' => [$e, 'warning', 1, 1, 0],
            'warning-111' => [$e, 'warning', 1, 1, 1],
            'warning-112' => [$e, 'warning', 1, 1, 2],
            'warning-115' => [$u, 'warning', 1, 1, 5],

            'error-000' => [$o, 'error', 0, 0, 0],
            'error-001' => [$o, 'error', 0, 0, 1],
            'error-002' => [$o, 'error', 0, 0, 2],
            'error-005' => [$u, 'error', 0, 0, 5],

            'error-010' => [$o, 'error', 0, 1, 0],
            'error-011' => [$o, 'error', 0, 1, 1],
            'error-012' => [$o, 'error', 0, 1, 2],
            'error-015' => [$u, 'error', 0, 1, 5],

            'error-100' => [$e, 'error', 1, 0, 0],
            'error-101' => [$e, 'error', 1, 0, 1],
            'error-102' => [$e, 'error', 1, 0, 2],
            'error-105' => [$u, 'error', 1, 0, 5],

            'error-110' => [$e, 'error', 1, 1, 0],
            'error-111' => [$e, 'error', 1, 1, 1],
            'error-112' => [$e, 'error', 1, 1, 2],
            'error-115' => [$u, 'error', 1, 1, 5],
        ];
    }

    /**
     * @dataProvider casesGetTaskExitCode
     *
     * @param int $expected
     * @param string $failOn
     * @param int $numOfErrors
     * @param int $numOfWarnings
     * @param int $exitCode
     */
    public function testGetTaskExitCode($expected, $failOn, $numOfErrors, $numOfWarnings, $exitCode)
    {
        /** @var RunTask $runTask */
        $runTask = Stub::construct(
            RunTask::class,
            [['failOn' => $failOn]],
            ['exitCode' => $exitCode]
        );

        static::assertEquals(
            $expected,
            static::getMethod('getTaskExitCode')->invokeArgs($runTask, [$numOfErrors, $numOfWarnings])
        );
    }

    /**
     * @return array
     */
    public function casesRun()
    {
        return [
            'withoutJar - success' => [
                0,
                [],
                false,
            ],
            'withoutJar - warning' => [
                1,
                [
                    'a.ts' => [
                        [
                            'severity' => 'warning',
                        ],
                    ],
                ],
                false,
            ],
            'withoutJar - error' => [
                2,
                [
                    'a.ts' => [
                        [
                            'severity' => 'error',
                        ],
                    ]
                ],
                false,
            ],
            'withJar - success' => [
                0,
                [],
                true,
            ],
            'withJar - warning' => [
                1,
                [
                    'a.ts' => [
                        [
                            'severity' => 'warning',
                        ],
                    ],
                ],
                true,
            ],
            'withJar - error' => [
                2,
                [
                    'a.ts' => [
                        [
                            'severity' => 'error',
                        ],
                    ],
                ],
                true,
            ],
        ];
    }

    /**
     * This way cannot be tested those cases when the lint process failed.
     *
     * @dataProvider casesRun
     *
     * @param int $expectedExitCode
     * @param array $expectedReport
     * @param bool $withJar
     */
    public function testRun($expectedExitCode, array $expectedReport, $withJar)
    {
        $options = [
            'workingDirectory' => 'my-working-dir',
            'assetJarMapping' => ['report' => ['tsLintRun', 'report']],
            'format' => 'yaml',
            'failOn' => 'warning',
            'convertFormatTo' => 'yaml2jsonGroupByFiles',
        ];

        /** @var RunTask $runTask */
        $runTask = Stub::construct(
            RunTask::class,
            [$options, []],
            [
                'processClass' => DummyProcess::class,
            ]
        );

        $output = new \Helper\Dummy\Output();
        DummyProcess::$exitCode = $expectedExitCode;
        DummyProcess::$stdOutput = json_encode($expectedReport);

        $runTask->setLogger($this->container->get('logger'));
        $runTask->setOutput($output);
        $assetJar = null;
        if ($withJar) {
            $assetJar = new AssetJar();
            $runTask->setAssetJar($assetJar);
        }

        $result = $runTask->run();

        static::assertEquals($expectedExitCode, $result->getExitCode(), 'Exit code');
        static::assertEquals(
            $options['workingDirectory'],
            DummyProcess::$instance->getWorkingDirectory(),
            'Working directory'
        );

        if ($withJar) {
            /** @var \Cheppers\LintReport\ReportWrapperInterface $reportWrapper */
            $reportWrapper = $assetJar->getValue(['tsLintRun', 'report']);
            static::assertEquals(
                $expectedReport,
                $reportWrapper->getReport(),
                'Output equals with jar'
            );
        } else {
            static::assertEquals(
                $expectedReport,
                json_decode($output->output, true),
                'Output equals without jar'
            );
        }
    }

    public function testRunFailed()
    {
        $exitCode = 1;
        $expectedReport = [
            'a.ts' => [
                [
                    'severity' => 'warning',
                ],
            ],
        ];
        $expectedReportJson = json_encode($expectedReport);
        $options = [
            'workingDirectory' => 'my-working-dir',
            'assetJarMapping' => ['report' => ['tsLintRun', 'report']],
            'failOn' => 'warning',
            'format' => 'yaml',
            'convertFormatTo' => 'yaml2jsonGroupByFiles',
        ];

        /** @var RunTask $task */
        $task = Stub::construct(
            RunTask::class,
            [$options, []],
            [
                'processClass' => DummyProcess::class,
            ]
        );

        DummyProcess::$exitCode = $exitCode;
        DummyProcess::$stdOutput = $expectedReportJson;

        $task->setConfig(Robo::config());
        $task->setLogger($this->container->get('logger'));
        $assetJar = new AssetJar();
        $task->setAssetJar($assetJar);

        $result = $task->run();

        static::assertEquals($exitCode, $result->getExitCode());
        static::assertEquals(
            $options['workingDirectory'],
            DummyProcess::$instance->getWorkingDirectory()
        );

        /** @var \Cheppers\LintReport\ReportWrapperInterface $reportWrapper */
        $reportWrapper = $assetJar->getValue(['tsLintRun', 'report']);
        static::assertEquals($expectedReport, $reportWrapper->getReport());
    }
}