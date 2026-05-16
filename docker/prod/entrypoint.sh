#!/bin/sh
# Hive prod entrypoint.
#
# Seeds the storage/ tree on first boot when the docker volume is empty —
# Laravel needs storage/framework/{cache,sessions,views} and friends to
# exist, but a fresh named volume mounts as an empty directory, shadowing
# what was baked into the image. We restore from /app/storage-init when
# that happens and stay out of the way on subsequent boots.
#
# The same image runs three roles (app / horizon / scheduler) through
# different CMDs in compose — this script is role-agnostic.

set -e

if [ -d /app/storage-init ] && [ -z "$(ls -A /app/storage 2>/dev/null)" ]; then
    echo "[hive] seeding empty storage/ from baked skeleton"
    cp -a /app/storage-init/. /app/storage/
    chown -R www-data:www-data /app/storage
fi

exec "$@"
