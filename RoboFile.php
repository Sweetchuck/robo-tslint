<?php

// @codingStandardsIgnoreStart
use Robo\Collection\CollectionBuilder;
use Sweetchuck\LintReport\Reporter\BaseReporter;
use Sweetchuck\LintReport\Reporter\CheckstyleReporter;
use League\Container\ContainerInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class RoboFile extends \Robo\Tasks
// @codingStandardsIgnoreEnd
{
    use \Sweetchuck\Robo\Git\GitTaskLoader;
    use \Sweetchuck\Robo\Phpcs\PhpcsTaskLoader;

    /**
     * @var array
     */
    protected $composerInfo = [];

    /**
     * @var array
     */
    protected $codeceptionInfo = [];

    /**
     * @var string
     */
    protected $packageVendor = '';

    /**
     * @var string
     */
    protected $packageName = '';

    /**
     * @var string
     */
    protected $binDir = 'vendor/bin';

    /**
     * @var string
     */
    protected $phpExecutable = 'php';

    /**
     * @var string
     */
    protected $phpdbgExecutable = 'phpdbg';

    /**
     * Allowed values: dev, git-hook, jenkins.
     *
     * @var string
     */
    protected $environment = '';

    public function __construct()
    {
        putenv('COMPOSER_DISABLE_XDEBUG_WARN=1');
        $this->initComposerInfo();
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container)
    {
        BaseReporter::lintReportConfigureContainer($container);

        return parent::setContainer($container);
    }

    /**
     * @return string
     */
    protected function getEnvironment(): string
    {
        if ($this->environment) {
            return $this->environment;
        }

        return getenv('ROBO_PHPCS_ENVIRONMENT') ?: 'dev';
    }

    /**
     * Git "pre-commit" hook callback.
     */
    public function githookPreCommit(): CollectionBuilder
    {
        $this->environment = 'git-hook';

        return $this
            ->collectionBuilder()
            ->addTask($this->taskComposerValidate())
            ->addTask($this->getTaskPhpcsLint())
            ->addTask($this->getTaskCodecept());
    }

    /**
     * Run the Robo unit tests.
     */
    public function test(): CollectionBuilder
    {
        return $this->getTaskCodecept();
    }

    /**
     * Run code style checkers.
     */
    public function lint(): CollectionBuilder
    {
        return $this
            ->collectionBuilder()
            ->addTask($this->taskComposerValidate())
            ->addTask($this->getTaskPhpcsLint());
    }

    /**
     * @return $this
     */
    protected function initComposerInfo()
    {
        if ($this->composerInfo || !is_readable('composer.json')) {
            return $this;
        }

        $this->composerInfo = json_decode(file_get_contents('composer.json'), true);
        list($this->packageVendor, $this->packageName) = explode('/', $this->composerInfo['name']);

        if (!empty($this->composerInfo['config']['bin-dir'])) {
            $this->binDir = $this->composerInfo['config']['bin-dir'];
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function initCodeceptionInfo()
    {
        if ($this->codeceptionInfo) {
            return $this;
        }

        if (is_readable('codeception.yml')) {
            $this->codeceptionInfo = Yaml::parse(file_get_contents('codeception.yml'));
        } else {
            $this->codeceptionInfo = [
                'paths' => [
                    'log' => 'tests/_output',
                ],
            ];
        }

        return $this;
    }

    protected function getTaskCodecept(): CollectionBuilder
    {
        $environment = $this->getEnvironment();
        $withCoverage = $environment !== 'git-hook';
        $withUnitReport = $environment !== 'git-hook';
        $logDir = $this->getLogDir();

        $cmdArgs = [];
        if ($this->isPhpDbgAvailable() && !$this->isPhpExtensionAvailable('xdebug')) {
            $cmdPattern = '%s -qrr %s';
            $cmdArgs[] = escapeshellcmd($this->phpdbgExecutable);
            $cmdArgs[] = escapeshellarg("{$this->binDir}/codecept");
        } else {
            $cmdPattern = '%s';
            $cmdArgs[] = escapeshellcmd("{$this->binDir}/codecept");
        }

        $cmdPattern .= ' --ansi';
        $cmdPattern .= ' --verbose';

        $tasks = [];
        if ($withCoverage) {
            $cmdPattern .= ' --coverage=%s';
            $cmdArgs[] = escapeshellarg('coverage/coverage.serialized');

            $cmdPattern .= ' --coverage-xml=%s';
            $cmdArgs[] = escapeshellarg('coverage/coverage.xml');

            $cmdPattern .= ' --coverage-html=%s';
            $cmdArgs[] = escapeshellarg('coverage/html');

            $tasks['prepareCoverageDir'] = $this
                ->taskFilesystemStack()
                ->mkdir("$logDir/coverage");
        }

        if ($withUnitReport) {
            $cmdPattern .= ' --xml=%s';
            $cmdArgs[] = escapeshellarg('junit/junit.xml');

            $cmdPattern .= ' --html=%s';
            $cmdArgs[] = escapeshellarg('junit/junit.html');

            $tasks['prepareJUnitDir'] = $this
                ->taskFilesystemStack()
                ->mkdir("$logDir/junit");
        }

        $cmdPattern .= ' run';

        if ($environment === 'jenkins') {
            // Jenkins has to use a post-build action to mark the build "unstable".
            $cmdPattern .= ' || [[ "${?}" == "1" ]]';
        }

        $tasks['runCodeception'] = $this->taskExec(vsprintf($cmdPattern, $cmdArgs));

        return $this
            ->collectionBuilder()
            ->addTaskList($tasks);
    }

    /**
     * @return \Sweetchuck\Robo\Phpcs\Task\PhpcsLintFiles|\Robo\Collection\CollectionBuilder
     */
    protected function getTaskPhpcsLint()
    {
        $env = $this->getEnvironment();

        $files = [
            'src/',
            'src-dev/Composer/',
            'tests/_data/RoboFile.php',
            'tests/_support/Helper/',
            'tests/acceptance/',
            'tests/unit/',
            'RoboFile.php',
        ];

        $options = [
            'failOn' => 'warning',
            'standards' => ['PSR2'],
            'lintReporters' => [
                'lintVerboseReporter' => null,
            ],
        ];

        if ($env === 'jenkins') {
            $options['failOn'] = 'never';

            $options['lintReporters']['lintCheckstyleReporter'] = (new CheckstyleReporter())
                ->setDestination('tests/_output/checkstyle/phpcs.psr2.xml');
        }

        if ($env !== 'git-hook') {
            return $this->taskPhpcsLintFiles($options + ['files' => $files]);
        }

        return $this
            ->collectionBuilder()
            ->addTask($this
                ->taskGitReadStagedFiles()
                ->setCommandOnly(true)
                ->setPaths($files))
            ->addTask($this
                ->taskPhpcsLintInput($options)
                ->deferTaskConfiguration('setFiles', 'files'));
    }

    protected function isPhpExtensionAvailable(string $extension): bool
    {
        $command = sprintf('%s -m', escapeshellcmd($this->phpExecutable));

        $process = new Process($command);
        $exitCode = $process->run();
        if ($exitCode !== 0) {
            throw new \RuntimeException('@todo');
        }

        return in_array($extension, explode("\n", $process->getOutput()));
    }

    protected function isPhpDbgAvailable(): bool
    {
        $command = sprintf(
            '%s -i | grep -- %s',
            escapeshellcmd($this->phpExecutable),
            escapeshellarg('--enable-phpdbg')
        );

        return (new Process($command))->run() === 0;
    }

    protected function getLogDir(): string
    {
        $this->initCodeceptionInfo();

        return !empty($this->codeceptionInfo['paths']['log']) ?
            $this->codeceptionInfo['paths']['log']
            : 'tests/_output';
    }
}
