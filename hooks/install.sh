#!/bin/sh

TOP=$(git rev-parse --show-toplevel);

mkdir -p $TOP/.git/hooks;

for hook in $(ls $TOP/hooks/*-commit)
do
    ln -s $hook $TOP/.git/hooks/;
done

echo '-> The hooks have been installed.';