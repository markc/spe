#!/usr/bin/env bash
# Created: 20170301 - Updated: 20250213
# Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

[[ $1 =~ '-h' ]] && echo "Usage: [bash] build.sh [path(pwd)]

Example:

cd ~/Dev/project
bash build.sh .
" && exit 1

[[ $1 ]] && cd $1

echo "<?php declare(strict_types = 1);
// Simple PHP Examples $(date -u +'%Y-%m-%d %H:%M:%S') UTC
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)
// This is single script concatenation of all PHP files in project at
// https://github.com/markc/spe
" > all.php

(
  find src -name "*.php" -exec cat {} +
  cat public/index.php
) | sed \
  -e '/^?>/d' \
  -e '/^<?php/d' \
  -e '/^declare.*/d' \
  -e '/^\/\/ Copyright.*/d' \
  -e '/^\/\/ Created.*/d' \
  -e '/^namespace.*/d' \
  -e '/^use.*/d' \
  -e '/^require*/d' \
  -e '/^$/d' >> all.php
