<?php
declare(strict_types=1);

function password_is_set(): bool
{
    return file_exists(password_file()) && trim((string) @file_get_contents(password_file())) !== '';
}

function current_setup_step(array $apiConfig, array $project): string
{
    if (!password_is_set()) {
        return 'password';
    }
    if (!api_key_is_configured($apiConfig)) {
        return 'api';
    }
    if (!project_profile_is_configured($project)) {
        return 'profile';
    }
    if (!knowledge_base_is_configured()) {
        return 'documents';
    }
    return 'done';
}

