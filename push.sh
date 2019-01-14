#!/bin/bash
set -e -u
cd "$(dirname "$0")"
rsync -av web_scripts/ /mit/sipb-www/web_scripts/lamp/
