<?php
declare(strict_types=1);

// Compatibility bootstrap: public entrypoints still include lib/app.php.
// Domain logic lives in small files under src/ so the system is maintainable.
require_once __DIR__ . '/../src/Runtime/constants.php';
require_once __DIR__ . '/../src/Runtime/paths.php';
require_once __DIR__ . '/../src/Runtime/http.php';
require_once __DIR__ . '/../src/Runtime/text.php';
require_once __DIR__ . '/../src/Runtime/rate_limit.php';

require_once __DIR__ . '/../src/Storage/AtomicWriter.php';
require_once __DIR__ . '/../src/Storage/JsonStore.php';
require_once __DIR__ . '/../src/Storage/LockManager.php';
require_once __DIR__ . '/../src/Repository/ProjectRepository.php';
require_once __DIR__ . '/../src/Repository/ApiConfigRepository.php';

require_once __DIR__ . '/../src/Config/api_config.php';
require_once __DIR__ . '/../src/Config/project_config.php';

require_once __DIR__ . '/../src/Project/frontend_content.php';

require_once __DIR__ . '/../src/Knowledge/chunks.php';
require_once __DIR__ . '/../src/Knowledge/retrieval.php';

require_once __DIR__ . '/../src/Config/setup_status.php';

require_once __DIR__ . '/../src/Ingestion/uploads.php';
require_once __DIR__ . '/../src/Ingestion/chunk_generation.php';
require_once __DIR__ . '/../src/AI/ProviderCapabilities.php';
require_once __DIR__ . '/../src/AI/ModelRequest.php';
require_once __DIR__ . '/../src/AI/ModelProvider.php';
require_once __DIR__ . '/../src/AI/gemini_client.php';
require_once __DIR__ . '/../src/AI/GeminiProvider.php';
require_once __DIR__ . '/../src/AI/OpenAiCompatibleProvider.php';
require_once __DIR__ . '/../src/AI/ModelGateway.php';
require_once __DIR__ . '/../src/Ingestion/document_ingestion.php';

require_once __DIR__ . '/../src/Profile/profile_generation.php';
require_once __DIR__ . '/../src/PublicApi/project_payload.php';
require_once __DIR__ . '/../src/Prompt/system_prompt.php';
