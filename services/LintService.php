<?php

declare(strict_types=1);

namespace app\services;

use app\models\JobTemplate;
use yii\base\Component;

/**
 * Runs ansible-lint against a job template's playbook and stores the result.
 *
 * Uses --profile production (strictest built-in profile).
 * Safe to call from both web (after save) and worker (after project sync).
 * If ansible-lint is not installed the result is stored as an informational message.
 */
class LintService extends Component
{
    public function runForTemplate(JobTemplate $template): void
    {
        $project = $template->project;
        if ($project === null) {
            $this->store($template, null, 'No project assigned to this template.');
            return;
        }

        $isManual = $project->scm_type === \app\models\Project::SCM_TYPE_MANUAL;

        if ($isManual) {
            $projectPath = $project->local_path;
            if (empty($projectPath)) {
                $this->store($template, null, 'No local path configured for this project.');
                return;
            }
        } else {
            /** @var ProjectService $projectService */
            $projectService = \Yii::$app->get('projectService');
            $projectPath    = $projectService->localPath($project);
        }

        if (!is_dir($projectPath)) {
            $message = $isManual
                ? "Project path not found: {$projectPath}"
                : 'Project workspace not found — sync the project first.';
            $this->store($template, null, $message);
            return;
        }

        $playbookPath = $projectPath . '/' . ltrim($template->playbook, '/');
        if (!file_exists($playbookPath)) {
            $this->store($template, null, "Playbook not found: {$template->playbook}\nSync the project to fetch the latest files.");
            return;
        }

        if (!$this->isAvailable()) {
            return;
        }

        [$output, $exitCode] = $this->execute($template->playbook, $projectPath);
        $this->store($template, $exitCode, $output ?: '(no output)');
    }

    private function isAvailable(): bool
    {
        exec('which ansible-lint 2>/dev/null', $out, $code);
        return $code === 0;
    }

    /**
     * @return array{string, int}  [combined output, exit code]
     */
    private function execute(string $playbook, string $cwd): array
    {
        $cmd = ['ansible-lint', '--profile', 'production', '--nocolor', $playbook];

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, $cwd);

        if (!is_resource($process)) {
            return ['Failed to start ansible-lint process.', -1];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $output = trim(($stdout ?: '') . ($stderr ? "\n" . $stderr : ''));
        return [$output, $exitCode];
    }

    private function store(JobTemplate $template, ?int $exitCode, string $output): void
    {
        $template->lint_output    = $output;
        $template->lint_at        = time();
        $template->lint_exit_code = $exitCode;
        $template->save(false, ['lint_output', 'lint_at', 'lint_exit_code']);
    }
}
