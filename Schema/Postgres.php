<?php

namespace Kanboard\Plugin\Ctec\Schema;

const VERSION = 2;

function version_2($pdo)
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS code_review (
        "id" SERIAL PRIMARY KEY
        "task_id" INTEGER,
        "name" TEXT
    )');
}

// https://docs.kanboard.org/en/latest/plugins/schema_migrations.html