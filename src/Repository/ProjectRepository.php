<?php
declare(strict_types=1);

final class ProjectRepository
{
    public function __construct(private JsonStore $jsonStore)
    {
    }

    public function load(): array
    {
        ensure_app_dirs();
        $data = $this->jsonStore->read(project_config_file(), []);
        if ($data === []) {
            return default_project_config();
        }

        return merge_project_config(default_project_config(), $data);
    }

    public function save(array $project): void
    {
        ensure_app_dirs();
        $this->jsonStore->write(project_config_file(), $project);
    }

    public function isConfigured(array $project): bool
    {
        return trim((string) ($project['title'] ?? '')) !== ''
            && trim((string) ($project['topic'] ?? '')) !== '';
    }
}

function project_repository(): ProjectRepository
{
    static $repository = null;
    if (!$repository instanceof ProjectRepository) {
        $repository = new ProjectRepository(new JsonStore(new AtomicWriter()));
    }

    return $repository;
}
