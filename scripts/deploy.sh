#!/usr/bin/env bash
set -euo pipefail

# Deploy Retaguide theme and mu-plugins to remote host via rsync over SSH.
# Requires SSH key-based auth. Usage: ./deploy.sh prod

ENVIRONMENT=${1:-prod}
CONFIG_FILE=".env"

if [[ ! -f ${CONFIG_FILE} ]]; then
  echo "Missing .env file. Copy .env.example and update credentials." >&2
  exit 1
fi

set -o allexport
source ${CONFIG_FILE}
set +o allexport

if [[ -z "${DEPLOY_HOST:-}" || -z "${DEPLOY_PATH:-}" || -z "${DEPLOY_USER:-}" ]]; then
  echo "DEPLOY_HOST, DEPLOY_PATH, and DEPLOY_USER must be set in .env" >&2
  exit 1
fi

RSYNC_EXCLUDES=(
  --exclude=.git
  --exclude=.github
  --exclude=node_modules
  --exclude=vendor
  --exclude=.env
)

TARGET="${DEPLOY_USER}@${DEPLOY_HOST}:${DEPLOY_PATH}/wp-content/"

echo "Syncing theme..."
rsync -avz --delete ${RSYNC_EXCLUDES[@]} wp-content/themes/retaguide "${TARGET}themes/"

echo "Syncing mu-plugins..."
rsync -avz --delete ${RSYNC_EXCLUDES[@]} wp-content/mu-plugins/ "${TARGET}mu-plugins/"

echo "Running remote cache flush..."
ssh -o StrictHostKeyChecking=no "${DEPLOY_USER}@${DEPLOY_HOST}" "cd ${DEPLOY_PATH} && wp cache flush && wp rewrite flush --hard"

echo "Deploy complete."
