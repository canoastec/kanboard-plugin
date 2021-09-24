<?php

namespace Kanboard\Plugin\Ctec\Schema;

const VERSION = 3;

function version_3($pdo)
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS pair_programming (
        "id" INTEGER PRIMARY KEY,
        "task_id" INTEGER,
        "name" TEXT,
        "assignee" TEXT
    )');
}

function version_2($pdo)
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS code_review (
        "id" INTEGER PRIMARY KEY,
        "task_id" INTEGER,
        "name" TEXT
    )');
}

// https://docs.kanboard.org/en/latest/plugins/schema_migrations.html