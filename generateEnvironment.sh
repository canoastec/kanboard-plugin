#!/bin/sh

PROJECT_ID=$1
QUERY_CURRENT_SPRINT=$2
GESTAOSISTEMAS_API=$3

#define the template.
cat  << EOF
PROJECT_ID=$PROJECT_ID
QUERY_CURRENT_SPRINT=$QUERY_CURRENT_SPRINT
GESTAOSISTEMAS_API=$GESTAOSISTEMAS_API

EOF
